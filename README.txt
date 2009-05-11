$Id$

Conditional Fields:
--------------------
A Drupal module


Author:
--------------------
Gregorio Magini (peterpoe) <gmagini@gmail.com> - http://www.wikirandom.org
Initially inspired by the Explainfield module -> http://drupal.org/project/explainfield


Short Description:
--------------------
Content fields and groups visibility based on the values of user defined "controlling" fields.


Description:
--------------------
Conditional Fields allows you to assign fields with allowed values as "controlling fields" for other fields and groups. When a field or group is "controlled", it will only be available for editing and displayed if the selected values of the controlling field match the "trigger values" assigned to it.
When editing a node, the controlled fields are dynamically shown and hidden with javascript.
You can, for example, make a custom "article teaser" field that is shown only if a "Has teaser" checkbox is checked.


Dependencies:
--------------------
- CCK / content.module -> http://drupal.org/project/cck
- Fieldgroups / fieldgroups.module (included in CCK) -> Not required, but if enabled you can also set groups as controlled fields.


Installation:
--------------------
- Copy the unpacked folder "conditional_fields" in drupal/sites/all
- Go to the modules administration page (admin/build/modules) and activate it (it’s in the CCK package)
- Assign the "Administer conditional fields" permission to the desired user roles.

Usage:
--------------------
Once the module is activated, a new set of options will appear in the field editing form, from where you can select which of the allowed values available will make the field "controlled". If "- Not controlling -" or no value is selected, the field will be shown as usual.

There is a "Conditional fields" tab in every content type admin page with the following options:

- User Interface options.
  * Javascript: You can decide if you want to use javascript to dynamically disable (grey out) or hide the fields when editing a node.
  * Animation: There are three animations currently available: show/hide (default), slide down/slide up, and fade in/fade out. You can also set the speed of the animation.

- Orphaned controlled fields settings.
  These settings control the visibility (on node view) and editability (on node edit) of controlled fields when the controlling fields are not visible (e.g.: set to 'Hidden' in the fields display settings) or not editable (e.g.: by an access control module).

- Administrators see all fields.
  If checked, users with 'administer conditional fields' permission will see all controlled fields of a node, even if the weren't triggered.

- Reset. 
  If checked, all conditional fields configurations for this content type will be reset (though the fields themselves will remain untouched).

Limitations:
--------------------
- Each field or group can be controlled from only one field (though a field can control any number of fields and groups). This is a bug, and will be corrected in later develpment.
- If the controlling field is in a group, it can only control or be controlled only by fields that are in the same group.
- Currently works only with checkbox, select, and radio controlling fields. Controlled fields can be of any type.
- There are reported incompatibilities with the following modules:
    * tinyMCE
    * Multigroup

To Do:
--------------------
Any help is welcome!
--------------------
Check the issue queue of this module for more information:
http://drupal.org/project/issues/conditional_fields

Bug: multiple controlling fields on the same field don’t work
Bug: some required fields are not correctly handled (e.g.: date)
Testing: test different types of CCK fields for compatibility
Feature: allow more kinds of controlling fields (e.g.: taxonomy)
Feature: allow nested conditional fields
Feature: views integration
Feature: add confirmation step to the reset option in conditional fields administration page
Feature: tune performance (using more static variables, adding cache)
