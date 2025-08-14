# Ship Restrict – Overview & Usage Guide

## Table of Contents
1. Introduction
2. Key Features
3. Compatibility Matrix
4. Administrator Guide
   1. Installing & Activating
   2. Settings Page Walk-Through
   3. Creating Restriction Rules
   4. Product-Level / Variation-Level Restrictions
   5. Managing the License
5. Customer Experience Flow
6. How-To Scenarios
7. Frequently Asked Questions (FAQ)

---

## 1. Introduction
Ship Restrict prevents specific WooCommerce products (or groups of products) from being shipped to restricted destinations in the United States. Administrators define rules by **state, city, ZIP code, category, or tag**. During checkout, the plugin blocks restricted items and displays a clear error so the shopper can adjust their cart.

---

## 2. Key Features
• Restrict by **State**, **City**, or **ZIP Code**  
• Rule scoping by **Product Category** or **Product Tag**  
• **Per-Product** and **Per-Variation** overrides  
• Customisable error message with `{product}` placeholder  
• Works with WooCommerce **High-Performance Order Storage** (HPOS)  
• License validation via **KeyForge**  
• Fully translatable (Text-Domain: `ship-restrict`)

---

## 3. Compatibility Matrix
| Component | Minimum | Tested Up To |
|-----------|---------|--------------|
| WordPress | 5.6     | 6.x          |
| PHP       | 7.2     | 8.2          |
| WooCommerce | 5.0   | 9.8          |
| HPOS      | ✔ Supported | — |

The plugin is self-contained and requires **no third-party shipping gateway hooks**—it simply validates the cart/checkout state via WooCommerce core hooks.

---

## 4. Administrator Guide
### 4.1 Installing & Activating
1. Upload `ship-restrict.zip` via **Plugins → Add New → Upload**.  
2. Activate the plugin.  
3. Navigate to **WooCommerce → Ship Restrict** to open the settings page.

### 4.2 Settings Page Walk-Through
1. **How to Use** – Quick tips for organising restrictions.  
2. **Restriction Rules** – Global rules based on categories or tags.  
3. **Error Message** – Customise the message (supports `{product}` token).  
4. **License Status** – Enter/validate your KeyForge licence key.

### 4.3 Creating Restriction Rules
1. Select **Rule Name**, **Rule Type** (Category/Tag) & the term.  
2. Pick the destination **States**; optionally specify **Cities** or **ZIP Codes**.  
3. Click **Add Rule**. The rule is enforced immediately.

### 4.4 Product-Level / Variation-Level Restrictions
Edit any product (or variation) and scroll to **Shipping → Ship Restrict**:  
• Pick restricted States (multi-select).  
• Enter comma-separated Cities or ZIP codes.  
These override rules and are ideal for edge-case SKUs.

### 4.5 Managing the License
Enter your KeyForge licence key and click **Save License**.  
• Valid keys show a green "license active" badge.  
• Invalid/expired keys show an admin notice and disable Pro-only features.

---

## 5. Customer Experience Flow
1. Shopper adds items to the cart.  
2. On the **Cart** or **Checkout** page, when a U.S. shipping address is entered/changed:  
   * The plugin scans all items against restrictions.  
   * If a conflict exists, checkout validation fails and the shopper sees:  
     > *Some items in your cart cannot be shipped to your address:*  
     > • *The {Product Name} cannot currently be shipped to your location. Please remove from cart to continue.*
3. Shopper removes or replaces the restricted items → checkout proceeds normally.

---

## 6. How-To Scenarios
| Scenario | Steps |
|----------|-------|
| Block "80% Frames" in CA & NY | 1) Tag all frames with `80-frames`  2) Add a rule: Name "80% Frames – CA + NY", Type = Tag, Term = `80-frames`, States = `CA`, `NY`. |
| Prevent large-capacity magazines in CO ≥ 15 rounds | Tag products `mag-capacity-15` and create a rule targeting `CO`. |
| Single product restricted in Chicago only | Edit the product → add `Chicago` in **Restricted Cities**. |

---

## 7. Frequently Asked Questions (FAQ)
**Q 1: Does the plugin block non-U.S. addresses?**  
A: No, restrictions only run when the shipping country = `US`.

**Q 2: Can I customise the notice text?**  
A: Yes—enter a message in **Error Message**. Use `{product}` to embed the product name.

**Q 3: Are taxes or shipping methods affected?**  
A: No. The plugin only prevents checkout of restricted items; it does not alter tax/shipping rates.

**Q 4: Will it work with HPOS / custom order tables?**  
A: Yes—Ship Restrict declares compatibility via `FeaturesUtil::declare_compatibility('custom_order_tables')`.

**Q 5: Where is my licence validated?**  
A: KeyForge validation/activation endpoints: `https://keyforge.dev/api/v1/public/licenses/*`.

**Q 6: What happens if the licence expires?**  
A: Pro features (rule engine, future updates & support) are disabled; existing restrictions remain active.

---

© UpNorth Media 2025 – Version 1.0.0 