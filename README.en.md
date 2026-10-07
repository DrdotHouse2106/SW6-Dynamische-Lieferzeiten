# Dynamic monthly delivery dates for Shopware 6.6

*[Deutsche Version](README.md) (primary documentation)*

Shopware 6 plugin (`MonthlyDeliveryPlugin`) that calculates a **fixed monthly shipping date** and the corresponding **order deadline** for products of specific suppliers. Both are shown on the product detail page, in the cart and off-canvas cart, on the checkout confirm and finish pages, in the order history and in emails.

**Each supplier can have its own rule** (delivery profiles).

## Calculation (default profile `standard`)

| | Meaning | Default |
|---|---|---|
| **L** | arrival at the shop | last Wednesday of the month |
| **D** | customer order deadline | L − 7 days (= second-to-last Wednesday), 23:59:59 |
| **V** | shipping date to the customer | Monday after L (= L + 5 days) |

- `now <= D` → this month's dates
- `now > D` → next month's dates

All dates are calculated in the configured shop timezone (default `Europe/Berlin`).

## Delivery profiles per supplier

**Settings → Extensions → Monthly delivery profiles**: arrival weekday and occurrence (first … fourth, second-to-last, last), deadline offset in days and time, shipping weekday (next weekday after arrival) and months without delivery (e.g. `8, 12`).

Profile resolution (first match wins):

1. product custom field `custom_supplier_monthly_delivery_profile`
2. manufacturer/supplier custom field `custom_supplier_monthly_delivery_manufacturer_profile`
3. default profile from the plugin configuration
4. built-in default rule

The custom fields accept the profile ID (admin selection) or its technical name (easy to write from an ERP).

## Activation and ERP protection (ERPNext / WeClapp)

- `product.deliveryTime` and `product.restockTime` are **never read or written**. ERP syncs can keep overwriting them.
- The plugin is activated **only** through the product custom field `custom_supplier_monthly_delivery == true` (variants inherit it from the parent).
- Only the storefront display is replaced. Inactive, sold-out closeout and pre-order products keep the default display.

## Storefront

| Location | Template |
|---|---|
| Product detail page (classic and CMS buy box) | `component/delivery-information.html.twig` |
| Cart, off-canvas, confirm, finish, order history (per item) | `component/line-item/type/product.html.twig` |
| Default delivery date at the line item (hidden for affected items) | `component/line-item/element/delivery-date.html.twig` |
| Summary on confirm / finish page | `page/checkout/confirm/index.html.twig`, `page/checkout/finish/index.html.twig` |
| Product listing (optional) | `component/product/card/box-standard.html.twig` |

Data: `product.extensions.monthlyDelivery`, `page.extensions.monthlyDelivery` and `lineItem.payload.monthlyDelivery`. The payload is persisted to `order_line_item.payload`, so orders keep the date promised at order time. Email template snippets are in the German [README](README.md#5-e-mail-vorlagen).

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate MonthlyDeliveryPlugin
bin/build-administration.sh
bin/console cache:clear
```

## HTTP cache

Pages that show a fixed shipping date get their `s-maxage` limited to the earliest order deadline on the page. This prevents stale dates after the deadline. Clear the cache after changing a profile.

## Tests

```bash
cd tests && composer install && vendor/bin/phpunit -c ../phpunit.xml.dist
```

## License

MIT – see [LICENSE](LICENSE).
