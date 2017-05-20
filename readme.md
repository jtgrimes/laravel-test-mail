This package is a combination of mail drivers built by Jeffrey Way and Mohammed Said.

Jeffrey Way wrote the "assertable" bits - where you can make assertions about sent mail.
The gist the code is based on is here: https://gist.github.com/JeffreyWay/b501c53d958b07b8a332

Mohammed Said wrote a package which saves copies of sent mail to disk so that they can be previewed in the browser.
His package is here: https://github.com/themsaid/laravel-mail-preview

All credit should go to Mr. Way and Mr. Said for doing the heavy lifting. If there are any errors, the blame should go to me.


##Configuration
This package expects the following fields in the `config/mail.php` file: 'preview_path' and 'preview_lifetime'
