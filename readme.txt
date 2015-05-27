=== Plugin Name ===
Contributors: alexandre67fr
Donate link: http://formidablepro2pdf.com/
Tags: fpropdf, pdf, generation, pdftk, formidable, forms
Requires at least: 3.0.1
Tested up to: 4.2.2
Stable tag: 1.6.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Exports Formidable Forms into PDF files.

== Description ==

Formidable Pro PDF Exporter allows to export Formidable Forms into PDF files with user-defined layout.

Features:

* Exporting into PDF
* Multiple layouts
* Shortcodes to download entries in PDF format
* Automatic downloads
* Locking layout after PDF generation

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip the archive and upload `fpropdf` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure that you have Formidable Forms plugin installed and activated, that you have at least one Formidable form, and at least one form entry.
4. Choose "New Field Map" in "Field Map to use" 
5. Click "Upload a PDF file" in "Field Map Designer" section. Press "Upload", and choose your new layout in "Layout to use"
6. After this, add some mappings for your PDF file in "Manage your custom layout here" section.
7. You can now use the shortcode or press "Export" button to download PDFs.

Please note that we use [PDFTK](https://www.pdflabs.com/docs/pdftk-man-page/) to fill in PDF files. This also means that your server has to have PHP shell commands enabled. If your server does not have `pdftk` installed, you can still use the plugin. In this case, you can generate 1 PDF file for 1 Formidable form.

Nevertheless, you can [purchase](http://formidablepro2pdf.com/) the plugin using [our website](http://formidablepro2pdf.com/). In this case, you do not need to install `pdftk` or to enable shell commands on your server. You can use our API according to the [Terms of Service](http://formidablepro2pdf.com/terms-of-service/). Just enter the activation code on the plugin options page.

== Frequently Asked Questions ==

= What are the requirements? =

You need to have Formidable Forms plugin installed and activated.

You also need to make sure that your server can execute shell commands, and that `pdftk` is installed on your server. 

You'll also need to have PHP `MB` or `iconv` extensions installed. They are usually installed on web servers.

If you want, you can purchase the plugin. In this case, no additional software installation is need.

= Does the plugin create PDF files? =

Not at this time. Currently the plugin populates pre-made PDF form fields with mapped data from Formidable Form and FormidablePro form fields.

Future plans include adding HTML to PDF capabilities.

= Is support offered for the free version? =

Yes - standard user support is available through the support forum or purchase a key code for premium level support.

= Does the plugin work with multisite installations? =

Yes, the plugin works with WordPress Multisite – site limits still apply.

== Screenshots ==

1. Plugin settings page.

== Changelog ==

= 1.6.0.7 =

Corrected encoding issues, added password protection, added button to duplicate layouts.

= 1.6.0.6 =

Removed PHP warnings, sorted WebForm Field IDs, removed unused WebForm Fields.

= 1.6.0.5 =

Updated plugin URL.

= 1.6.0.4 =

Signature plugin.

= 1.6.0.3 =

Bugfixing, email attachments.

= 1.6.0.2 =

Removed default form files, Tested compatibility with WordPress 4.2.2

= 1.6.0.1 =

Tested compatibility with WordPress 4.2.1 and Formidable Forms 2.0.04

= 1.6 =

Added shortcodes, export into PDF, API interface, Fields Map Designer.

== Upgrade Notice ==

= 1.6 =

First version of the plugin with basic functionality.
