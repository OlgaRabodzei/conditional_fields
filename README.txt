Conditional Fields:
-------------------
A Drupal 5 module


Author:
-------
Gregorio Magini (peterpoe) <gmagini@gmail.com>
Initially inspired by the Explainfield module -> http://drupal.org/project/explainfield


Short Description:
------------------
Content fields and groups visibility based on the values of user defined 'trigger' fields.


Description:
------------
Conditional Fields allows you to assign fields with allowed values as ‘controlling fields’ for other fields and groups. When a field or group is ‘controlled’, it will only be available for editing and displayed if the selected values of the controlling field match the ‘trigger values’ assigned to it.
When editing a node, the controlled fields are dynamically shown and hidden with javascript.
You can, for example, make a custom ‘article teaser’ field that is shown only if a ‘Has teaser’ checkbox is checked.


Dependencies:
-------------
- CCK / content.module -> http://drupal.org/project/cck
- Fieldgroups / fieldgroups.module (included in CCK) -> Not required, but if enabled can also set groups as conditionals.


Installation:
-------------
- Copy the unpacked folder ‘conditional_fields’ in drupal/sites/all
- Go to the modules administration page (admin/build/modules) and activate it (it’s in the CCK package)


Usage:
------
Once the module is activated, a new set of options will appear in the field editing form, from where you can select which of the allowed values available will make the field ‘controlled’. If no value is selected, the field will be shown as usual.
There is a ‘Conditional fields’ tab in the content type admin page with a couple of options.


Limitations:
------------
- Each field or group can be controlled from only one field (though a field can control any number of fields and groups). This is a bug, and will be corrected in later develpment.
- If the controlling field is in a group, it can only control fields form within the same group.
- Currently works only with checkbox, select, and radio controlling fields (though controlled field can be of any type).


To Do:
------
Bug: multiple controlling fields on the same field don’t work
Bug: when exporting/importing conditional fields, some trigger values are not saved
Bug: some required fields are not correctly handled (e.g.: date)
Feature: tune performance (using more static variables, adding cache)
Feature: views integration
Feature: port to drupal 6 (when cck is ported)
Test: test with pgsql
Test: test different types of cck field for compatibility
