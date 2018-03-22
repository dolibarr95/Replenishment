# Replenishment
Create suppliers orders from customer orders.

## Install
1. Transfer this module in your custom folder
2. Create an extrafield in Suppliers
3. Activate the module

## Extrafield
Complementary attributes (orders)

| Attribute         | Value  |
| -------------     | -----: |
| Label    |As you want|
| Attribute code    |reappro|
| Type | Select from table|
| Value | `commande:ref:rowid` |
| Position | 0 |
| Default value (Database) ||
| Unique ||
| Can always be edited | Yes |
| Hidden ||
| Show by default on list view ||

## Operating mode
From selected Validated supplier orders create or update draft suppliers orders.

From the selected supplier orders :
1. group lines by products
2. For each product count the needs, then substract the quantity already in order (draft, validated, approved).
3. Works with stock alerts
4. The supplier orders will be tagged with the custom orders.

1. Search a supplier order from the user with the product (update the line with new quantity best price)
2. Search a supplier order with this product (update the line with new quantity best price)
3. Search a supplier order (create the line)
4. If no result (create the supplier order)


## Warning
First test in non production Dolibarr.
