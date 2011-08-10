<?php
/*
   Copyright 2011 Michael L. Baker

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

   Notes: Based on the Time Tracking plugin by Elmar:
   2005 by Elmar Schumacher - GAMBIT Consulting GmbH
   http://www.mantisbt.org/forums/viewtopic.php?f=4&t=589	
*/


   auth_reauthenticate();
   access_ensure_global_level( plugin_config_get( 'admin_threshold' ) );
   html_page_top( plugin_lang_get( 'configuration' ) );
?>

<br />
<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
   <?php echo form_security_field( 'plugin_TimeTracking_config_update' ) ?>
   <table class="width60" align="center" cellspacing="1">
      <tr>
         <td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?></td>
      </tr>
      <tr <?php echo helper_alternate_class() ?>>
         <td class="category"><?php echo plugin_lang_get( 'view_threshold' ) ?></td>
         <td><select name="view_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold' ) ) ?></select></td>
      </tr>
      <tr <?php echo helper_alternate_class() ?>>
         <td class="category"><?php echo plugin_lang_get( 'add_threshold' ) ?></td>
         <td><select name="add_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'add_threshold' ) ) ?></select></td>
      </tr>
      <tr <?php echo helper_alternate_class() ?>>
         <td class="category"><?php echo plugin_lang_get( 'delete_threshold' ) ?></td>
         <td><select name="delete_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'delete_threshold' ) ) ?></select></td>
      </tr>
      <tr <?php echo helper_alternate_class() ?>>
         <td class="category"><?php echo plugin_lang_get( 'admin_threshold' ) ?></td>
         <td><select name="admin_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'admin_threshold' ) ) ?></select></td>
      </tr>
      <tr>
         <td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update' ) ?>"/></td>
      </tr>
   </table>
</form>

<?php
   html_page_bottom(); 
?>
