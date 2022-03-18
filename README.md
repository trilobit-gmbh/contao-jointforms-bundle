Joint Forms Bundle
==================

This is a bundle for [Contao CMS] (version 4.9.x).

Dies ist ein Bundle für [Contao CMS] (Version 4.9.x).

Mit diesem Bundle werden Formular-Abläufe und Abhängigkeiten definiert. Die Basis für die Speicherung der Daten ist die Contao Mitgliederverwaltung.
Abhängigkeiten von Formularen untereinander sind möglich und können mit der Symfony ExpressionLanguage definiert werden.
Einzelformulare können separate ausgefüllt und abgeschickt werden. Die Daten werden dann beim angemeldeten Mitglied persistiert.
Das Ausfüllen der weiteren und noch offenen Formulare kann jederzeit wieder aufgenommen werden.
Inhaltselemente können ergänzend auch über die Symfony ExpressionLanguage gesteuert werden. Es stehen die Daten der bereits ausgefüllten Formulare zur Verfügung.

Installation
------------

Install the extension via composer: [trilobit-gmbh/contao-jointforms-bundle](https://packagist.org/packages/trilobit-gmbh/contao-jointforms-bundle).

Kompatibilität / Getestet / Compatibility / Tested
--------------------------------------------------

- Contao version ~4.9

Configuration
-------------

You can define the following configuration parameters via your `config.yml` file:

```yaml
# example
trilobit_jointforms:
  environments:
    travelgrants:
      defaultPageIds:
        tl_form: 16
        tl_node: 12
      checkPdf: true
      items:
        -
          type: 'tl_page'
          id: 17
          class: 'logout'
        -
          type: 'tl_page'
          id: 12
          class: 'instructions'
        -
          type: 'tl_form'
          intern: 'Personal details'
          id: 16
        -
          type: 'tl_form'
          intern: 'Address of current place of work'
          id: 2
          visible_expression: 'jointforms.form16 && jointforms.form16.jointforms_complete'
        -
          type: 'tl_form'
          intern: 'Address for correspondence'
          id: 25
          visible_expression: 'jointforms.form16 && jointforms.form16.jointforms_complete && jointforms.form2 && jointforms.form2.use_this_address==="no"'
        -
          type: 'tl_form'
          intern: 'Upload of documents'
          id: 22
          visible_expression: 'jointforms.form16 && jointforms.form16.jointforms_complete'
        -
          type: 'tl_form'
          intern: 'Submit'
          id: 17
          submit: true
        -
          type: 'tl_page'
          id: 19
          class: 'delete'
```
example configuration

**todo**

Doku
* JS-Trigger Formular
* Symfony ExpressionLanguage Beispiele
* Screenshots
* app.tools.dateDiff(dateA, dateB, 'days') `//days, y, m, d, h, i, s`
* app.date `//Date::parse('Y-m-d');`
* app.time `//Date::parse('H:i');`
* app.tstamp `//time();`

Funktionalität
* Inhaltselemente Auswahl der Konfiguration
* Weitere Felder auswählbar machen
  * Templates
  * Gruppenbeschränkungen
  * Laufzeit

Kompatibilität
* Contao 4.13
* PHP 8

Veröffentlichen
* GitHub
* Packagist