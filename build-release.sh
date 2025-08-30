#!/bin/bash

# Ship Restrict WordPress Plugin Release Builder
# Automatically creates a bundled version ready for WordPress upload

set -e

# Configuration
PLUGIN_SLUG="ship-restrict"
PLUGIN_FILE="ship-restrict.php"
BUILD_DIR="build"
README_FILE="readme.txt"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Ship Restrict Release Builder${NC}"
echo "=================================="

# Check if plugin file exists
if [ ! -f "$PLUGIN_FILE" ]; then
    echo -e "${RED}❌ Error: $PLUGIN_FILE not found${NC}"
    exit 1
fi

# Get current version from plugin file (macOS compatible)
CURRENT_VERSION=$(grep "Version:" $PLUGIN_FILE | head -1 | sed 's/.*Version: *\([0-9]*\.[0-9]*\.[0-9]*\).*/\1/')
CURRENT_CLASS_VERSION=$(grep "const VERSION =" $PLUGIN_FILE | head -1 | sed "s/.*const VERSION = '\([0-9]*\.[0-9]*\.[0-9]*\)'.*/\1/")

if [ -z "$CURRENT_VERSION" ]; then
    echo -e "${RED}❌ Error: Could not extract current version from $PLUGIN_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}Current version: ${YELLOW}$CURRENT_VERSION${NC}"

# Prompt for new version
echo -e "\n${BLUE}Enter new version (current: $CURRENT_VERSION):${NC}"
read -p "New version: " NEW_VERSION

if [ -z "$NEW_VERSION" ]; then
    echo -e "${RED}❌ Error: Version cannot be empty${NC}"
    exit 1
fi

# Validate version format (x.y.z)
if ! [[ $NEW_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}❌ Error: Version must be in format x.y.z (e.g., 1.2.3)${NC}"
    exit 1
fi

# Check if readme.txt exists
if [ ! -f "$README_FILE" ]; then
    echo -e "${RED}❌ Error: $README_FILE not found${NC}"
    exit 1
fi

# Get current date in YYYY-MM-DD format
RELEASE_DATE=$(date +%Y-%m-%d)

# Prompt for changelog entry with categories
echo -e "\n${BLUE}Enter changelog notes for version $NEW_VERSION:${NC}"
echo -e "${YELLOW}Use categories: Added, Changed, Fixed, Removed, Security${NC}"
echo -e "${YELLOW}Format: [category] description (e.g., 'Added: New feature X')${NC}"
echo -e "${YELLOW}(Enter each change on a new line, press Enter twice when done)${NC}"

# Initialize arrays for each category
declare -a ADDED_ENTRIES
declare -a CHANGED_ENTRIES
declare -a FIXED_ENTRIES
declare -a REMOVED_ENTRIES
declare -a SECURITY_ENTRIES

while IFS= read -r line; do
    if [ -z "$line" ]; then
        # Check if we have any entries before breaking
        total_entries=$((${#ADDED_ENTRIES[@]} + ${#CHANGED_ENTRIES[@]} + ${#FIXED_ENTRIES[@]} + ${#REMOVED_ENTRIES[@]} + ${#SECURITY_ENTRIES[@]}))
        if [ $total_entries -gt 0 ]; then
            break
        fi
    elif [ -n "$line" ]; then
        # Parse the category from the line
        if [[ $line =~ ^[Aa]dded:?[[:space:]]*(.*) ]]; then
            ADDED_ENTRIES+=("${BASH_REMATCH[1]}")
        elif [[ $line =~ ^[Cc]hanged:?[[:space:]]*(.*) ]]; then
            CHANGED_ENTRIES+=("${BASH_REMATCH[1]}")
        elif [[ $line =~ ^[Ff]ixed:?[[:space:]]*(.*) ]]; then
            FIXED_ENTRIES+=("${BASH_REMATCH[1]}")
        elif [[ $line =~ ^[Rr]emoved:?[[:space:]]*(.*) ]]; then
            REMOVED_ENTRIES+=("${BASH_REMATCH[1]}")
        elif [[ $line =~ ^[Ss]ecurity:?[[:space:]]*(.*) ]]; then
            SECURITY_ENTRIES+=("${BASH_REMATCH[1]}")
        else
            # Default to Added if no category specified
            echo -e "${YELLOW}No category specified, adding to 'Added' category${NC}"
            ADDED_ENTRIES+=("$line")
        fi
    fi
done

# Check if we have any entries
total_entries=$((${#ADDED_ENTRIES[@]} + ${#CHANGED_ENTRIES[@]} + ${#FIXED_ENTRIES[@]} + ${#REMOVED_ENTRIES[@]} + ${#SECURITY_ENTRIES[@]}))
if [ $total_entries -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Warning: No changelog entries provided${NC}"
fi

# Update readme.txt with new version and changelog
if [ $total_entries -gt 0 ]; then
    echo -e "${BLUE}📝 Updating readme.txt changelog${NC}"
    
    # Create temp file
    TEMP_README=$(mktemp)
    
    # Build the new changelog entry with Keep a Changelog format
    NEW_ENTRY="= $NEW_VERSION - $RELEASE_DATE =\n"
    
    # Add each category if it has entries
    if [ ${#ADDED_ENTRIES[@]} -gt 0 ]; then
        NEW_ENTRY="${NEW_ENTRY}### Added\n"
        for entry in "${ADDED_ENTRIES[@]}"; do
            NEW_ENTRY="${NEW_ENTRY}* $entry\n"
        done
        NEW_ENTRY="${NEW_ENTRY}\n"
    fi
    
    if [ ${#CHANGED_ENTRIES[@]} -gt 0 ]; then
        NEW_ENTRY="${NEW_ENTRY}### Changed\n"
        for entry in "${CHANGED_ENTRIES[@]}"; do
            NEW_ENTRY="${NEW_ENTRY}* $entry\n"
        done
        NEW_ENTRY="${NEW_ENTRY}\n"
    fi
    
    if [ ${#FIXED_ENTRIES[@]} -gt 0 ]; then
        NEW_ENTRY="${NEW_ENTRY}### Fixed\n"
        for entry in "${FIXED_ENTRIES[@]}"; do
            NEW_ENTRY="${NEW_ENTRY}* $entry\n"
        done
        NEW_ENTRY="${NEW_ENTRY}\n"
    fi
    
    if [ ${#REMOVED_ENTRIES[@]} -gt 0 ]; then
        NEW_ENTRY="${NEW_ENTRY}### Removed\n"
        for entry in "${REMOVED_ENTRIES[@]}"; do
            NEW_ENTRY="${NEW_ENTRY}* $entry\n"
        done
        NEW_ENTRY="${NEW_ENTRY}\n"
    fi
    
    if [ ${#SECURITY_ENTRIES[@]} -gt 0 ]; then
        NEW_ENTRY="${NEW_ENTRY}### Security\n"
        for entry in "${SECURITY_ENTRIES[@]}"; do
            NEW_ENTRY="${NEW_ENTRY}* $entry\n"
        done
        NEW_ENTRY="${NEW_ENTRY}\n"
    fi
    
    # Look for [Unreleased] section and insert after it, or after == Changelog ==
    awk -v new_entry="$NEW_ENTRY" '
    /^= \[Unreleased\]/ {
        print
        found_unreleased = 1
        next
    }
    /^= [0-9]+\.[0-9]+\.[0-9]+/ && found_unreleased {
        printf "%s", new_entry
        found_unreleased = 0
        print
        next
    }
    /^== Changelog ==/ && !found_unreleased {
        print
        getline
        if ($0 ~ /^= \[Unreleased\]/) {
            print
        } else {
            printf "%s", new_entry
            print
        }
        next
    }
    { print }
    ' $README_FILE > $TEMP_README
    
    mv $TEMP_README $README_FILE
fi

# Update version in plugin file
echo -e "${BLUE}🔄 Updating version in $PLUGIN_FILE${NC}"

# Create backup
cp $PLUGIN_FILE "${PLUGIN_FILE}.backup"

# Update plugin header version
sed -i.tmp "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" $PLUGIN_FILE

# Update class constant version
sed -i.tmp "s/const VERSION = '$CURRENT_CLASS_VERSION'/const VERSION = '$NEW_VERSION'/" $PLUGIN_FILE

# Remove temp file
rm "${PLUGIN_FILE}.tmp"

# Update Stable tag in readme.txt
echo -e "${BLUE}🔄 Updating Stable tag in readme.txt${NC}"
sed -i.tmp "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" $README_FILE
rm "${README_FILE}.tmp"

# Create build directory
echo -e "${BLUE}📁 Creating build directory${NC}"
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR

# Create plugin directory in build
PLUGIN_BUILD_DIR="$BUILD_DIR/$PLUGIN_SLUG"
mkdir -p $PLUGIN_BUILD_DIR

# Copy plugin files
echo -e "${BLUE}📦 Copying plugin files${NC}"
cp $PLUGIN_FILE $PLUGIN_BUILD_DIR/
cp $README_FILE $PLUGIN_BUILD_DIR/

# Copy any additional assets if they exist
if [ -d "assets" ]; then
    cp -r assets $PLUGIN_BUILD_DIR/
    echo -e "${BLUE}📁 Copied assets directory${NC}"
fi

# Copy license file if it exists
if [ -f "LICENSE" ] || [ -f "LICENSE.txt" ]; then
    cp LICENSE* $PLUGIN_BUILD_DIR/ 2>/dev/null || true
    echo -e "${BLUE}📄 Copied license file${NC}"
fi

# Create ZIP file
echo -e "${BLUE}🗜️  Creating ZIP file${NC}"
cd $BUILD_DIR
ZIP_FILE="${PLUGIN_SLUG}-${NEW_VERSION}.zip"
zip -r $ZIP_FILE $PLUGIN_SLUG/
cd ..

# Calculate file size
FILE_SIZE=$(ls -lh "$BUILD_DIR/$ZIP_FILE" | awk '{print $5}')

echo -e "\n${GREEN}✅ Release build completed!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Version:${NC} $CURRENT_VERSION → $NEW_VERSION"
echo -e "${BLUE}ZIP file:${NC} $BUILD_DIR/$ZIP_FILE"
echo -e "${BLUE}Size:${NC} $FILE_SIZE"
echo -e "${BLUE}Ready for WordPress upload!${NC}"

# Offer to restore backup if something went wrong
echo -e "\n${YELLOW}Backup created at: ${PLUGIN_FILE}.backup${NC}"
echo -e "${YELLOW}Run 'mv ${PLUGIN_FILE}.backup ${PLUGIN_FILE}' to restore if needed${NC}"

# Show next steps
echo -e "\n${BLUE}Next Steps:${NC}"
echo -e "1. Test the plugin with the new version"
echo -e "2. Upload $BUILD_DIR/$ZIP_FILE to WordPress.org"
echo -e "3. Commit changes to git: git add . && git commit -m 'Release v$NEW_VERSION'"
echo -e "4. Create git tag: git tag v$NEW_VERSION && git push origin v$NEW_VERSION"
echo -e "5. Remove backup: rm ${PLUGIN_FILE}.backup"
echo -e "\n${BLUE}WordPress.org Release Process:${NC}"
echo -e "• The 'Stable tag' in readme.txt determines the current release"
echo -e "• Changelog follows Keep a Changelog format with categories"
echo -e "• Test thoroughly before releasing to avoid user issues"
echo -e "\n${BLUE}Changelog Categories:${NC}"
echo -e "• ${GREEN}Added${NC} - New features"
echo -e "• ${YELLOW}Changed${NC} - Changes to existing functionality"
echo -e "• ${RED}Fixed${NC} - Bug fixes"
echo -e "• ${RED}Removed${NC} - Removed features"
echo -e "• ${RED}Security${NC} - Security fixes and vulnerabilities"