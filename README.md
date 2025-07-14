
# GG.deals WooCommerce Price Sync

Automatically sync your WooCommerce product prices using the latest data from [GG.deals](https://gg.deals), based on Steam App IDs. This plugin is ideal for digital goods sellers, key resellers, or gaming communities that want to stay competitive with live pricing.

---

## 🔧 Features

- ✅ Syncs prices based on Steam App IDs via GG.deals API  
- 📦 Updates `_price`, `_sale_price`, and `_regular_price` fields in WooCommerce  
- 🗂️ Logs sync events to `/wp-content/uploads/ggdeals-sync.log`  
- 🔍 Includes manual sync button in the WooCommerce admin submenu  
- 💬 Adds detailed debug logs for troubleshooting  

---

## 📦 Installation

1. Download or clone the repository to `/wp-content/plugins/`
2. Activate the plugin via **Plugins > Installed Plugins**
3. Edit your WooCommerce products and add the custom field `_steam_app_id` with the correct Steam App ID
4. Navigate to **WooCommerce > GG.deals Price Sync**
5. Click **🔄 Sync Now** to begin syncing prices

---

## 🧠 How It Works

- The plugin uses the official GG.deals Prices API.
- It fetches current price data for each product with a `_steam_app_id`.
- It applies a 15% markup (by default) and updates:
  - **_price** → The current displayed price
  - **_sale_price** → Discounted price (calculated)
  - **_regular_price** → Retail price from GG.deals

---

## 🪵 Logging

All sync actions are logged to:



/wp-content/uploads/ggdeals-sync.log



Example log entries include:

text
[2025-07-14 02:33:52] Fetched batch data for Steam App IDs: 281112,1926520
[2025-07-14 02:33:52] Updated product ID 2506 prices: regular £49.99, sale £39.99



## 🚀 Premium Version (Coming Soon)

Planned enhancements include:

* 🔁 Auto sync schedule (hourly, 6h, daily)
* 🔑 License key validation system
* 📡 Webhook support for external automation
* 🔔 Optional logging to Discord or Slack via webhooks

---

## ⚙️ Requirements

* WordPress 5.6+
* WooCommerce 5.0+
* GG.deals API key (free from your [GG.deals account settings](https://gg.deals))

---

## 📄 License

License: PC Bros Ltd Non-Commercial License – see LICENSE for details.

---

## 🧑‍💼 Developed by

**PC Bros Ltd**
For support or custom development, email: support@pcbrosltd.co.uk

---

## 💡 Tips

* Use SpeedyCache or a similar plugin? Consider clearing cache post-sync to see frontend updates.
* Don’t forget to enable logging and debug mode while testing your setup.

```

