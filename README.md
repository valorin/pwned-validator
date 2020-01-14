# Pwned Passwords Validator for Laravel

The Pwned Password validator checks the user's submitted password (in a registration or password change form) with the awesome 
[HIBP Pwned Passwords](https://haveibeenpwned.com/Passwords) service to see if it is a known _pwned password_.
If the password has been pwned, it will fail validation, preventing the user from using that password in your app.

> Pwned Passwords are half a billion real world passwords previously exposed in data breaches. This exposure makes them unsuitable for ongoing use as they're at much greater risk of being used to take over other accounts.

This uses the _ranged search_ feature of the Pwned Passwords API, which uses [k-anonymity](https://en.wikipedia.org/wiki/K-anonymity)
to significantly reduce the risk of any information leakage when accessing the API.
For most systems this should be more than secure enough, although you should definitely decide for yourself if it's suitable for your app. 

Please make sure to check out the blog post by Troy Hunt, where he explains how the service works:
<https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/>.  

Troy worked with Cloudflare on this service, and they have an in depth technical analysis on how it works and the security implications: 
<https://blog.cloudflare.com/validating-leaked-passwords-with-k-anonymity/>.

Ultimately, it's up to you to decide if it's safe for your app or not.

## Installation

Install the package using Composer:

```
composer require valorin/pwned-validator
```

Laravel's service provider discovery will automatically configure the Pwned service provider for you.

Add the validation message to your validation lang file:

For each language add a validation message to `validation.php` like below

```
'pwned' => 'The :attribute is not secure enough',
```

## Using the `pwned` validator

After installation, the `pwned` validator will be available for use directly in your validation rules.
```php
'password' => 'pwned',
```

Within the context of a registration form, it would look like this:
```php
return Validator::make($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:6|pwned|confirmed',
]);
```

## Using the Rule Object

Alternatively, you can use the `Valorin\Pwned\Pwned` [Validation Rule Object](https://laravel.com/docs/5.5/validation#using-rule-objects)
instead of the `pwned` alias if you prefer:

```php
return Validator::make($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => ['required', 'string', 'min:6', new \Valorin\Pwned\Pwned, 'confirmed'],
]);
```

## Validation message

You will need to assign your own validation message within the `resources/lang/*/validation.php` file(s).
Both the Rule object and the `pwned` validator alias refer to the validation string `validation.pwned`.

I haven't set a default language string as it is important you get the language right for your intended users. 
In some systems a message like `Your password has been pwned! Please use a new one!` is suitable, while in other systems
you'd be better with something a lot longer:
 
> Your password is insufficiently secure as it has been found in known password breaches, please choose a new one. [Need help?](#)

Thanks to [kanalumaddela](https://github.com/valorin/pwned-validator/pull/2), you can use `:min` in the message to indicate the minimum number of times found set on the validator.

> Your password is insufficiently secure as it has been found at least :min times in known password breaches, please choose a new one.

## Limiting by the number of times the password was pwned

You can also limit rejected passwords to those that have been pwned a minimum number of times.
For example, `password` has been pwned 3,303,003 times, however `P@ssword!` has only been pwned 118 times.
If we wanted to block `password` but not `P@ssword!`, we can specify the minimum number as 150 like this:

```php
'password' => 'required|string|min:6|pwned:150|confirmed',
```

or using the Rule object:
```php
'password' => ['required', 'string', 'min:6', new \Valorin\Pwned\Pwned(150), 'confirmed'],
```
 
## FAQs

Q: How secure is this?  
A: Please check the above linked blog posts by Troy Hunt and Cloudflare, as they will answer your question and help you decide if it's safe enough for you.

Q: Do you do any caching?  
A: Yep! Each prefix query is cached for a week, to prevent constant API requests if the same prefix is checked multiple times. 

Q: Where are the tests?  
A: To properly test this code, we need to hit the web service. I don't want to automate that, to avoid abusing this fantastic service. Instead, since it is an incredibly simplistic validator, I've opted to manually test it for now. 
