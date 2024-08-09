# bitrix-payment

## Result output

Output module is `flashpay.payment` with all files by path `/src/module/flashpay.payment`

It is in UTF-8 encoding by default. But encoding Windows 1251 also required for some merchants.

So you need to create copy of `flashpay.payment` and encode its contents with Windows 1251.

Final result could be .zip archive with structure like this:

````
. bitrix
.. UTF-8
... flashpay.payment
.... contents of module
.. Windows 1251
... flashpay.payment
.... contents of module
````

## Finalization

Finalization happens only by webhooks now. Check status is possible option for future development of module.

Webhook URL for configs in Flashpay is `https://merchant-site/personal/order/flashpay/notification.php`

On success webhook payment will be set as success. But order will stay on old status even if all sum is paid.
One of the reasons is values of status in Bitrix. Normally it is `N` - for new not paid; and `P` - for paid order. 
But merchant could change these statuses: add new, edit default, even delete them.

If success webhook has another amount, then sum in bitrix payment will be changed to new and finalized as success.
Whole order meanwhile will stay with same status so worker of merchant could handle it manually.

## TODO

- error on payment page if 2 or more flashpay payments at order
- maybe add check status & events for it
- make it easier to set logotype for payment system
