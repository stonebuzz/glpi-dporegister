<?php
/*
 -------------------------------------------------------------------------
 DPO Register plugin for GLPI
 Copyright (C) 2018 by the DPO Register Development Team.

 https://github.com/karhel/glpi-dporegister
 -------------------------------------------------------------------------

 LICENSE

 This file is part of DPO Register.

 DPO Register is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 DPO Register is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with DPO Register. If not, see <http://www.gnu.org/licenses/>.

 --------------------------------------------------------------------------

  @package   dporegister
  @author    Karhel Tmarr
  @copyright Copyright (c) 2010-2013 Uninstall plugin team
  @license   GPLv3+
             http://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/karhel/glpi-dporegister
  @since     2018
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginDporegisterProcessing_SecurityMesure extends CommonDBRelation
{
    static public $itemtype_1 = 'PluginDporegisterProcessing';
    static public $items_id_1 = 'processings_id';

    static public $itemtype_2 = 'PluginDporegisterSecurityMesure';
    static public $items_id_2 = 'securitymesures_id';

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
    * Install or update PluginDporegisterProcessing_SecurityMesure
    *
    * @param Migration $migration Migration instance
    * @param string    $version   Plugin current version
    *
    * @return boolean
    */
    public static function install(Migration $migration, $version)
    {
        global $DB;
        $table = self::getTable();

        if (!TableExists($table)) {

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `processings_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_processings (id)',
                `securitymesures_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_securitymesures (id)',
                `description` varchar(255) NOT NULL default '',

                PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }
    }

    /**
    * Uninstall PluginDporegisterProcessing_SecurityMesure
    *
    * @return boolean
    */
    public static function uninstall()
    {
        global $DB;
        $table = self::getTable();

        if (TableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->query($query) or die("error deleting $table " . $DB->error());
        }
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return _n('Link Processing/Security Mesure', 'Links Processing/Security Mesures', $nb, 'dporegister');
    }

    //! @copydoc CommonGLPI::displayTabContentForItem($item, $tabnum, $withtemplate)
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // Check ACL
        if (!$item->canView()) {
            return false;
        }

        // Check item type
        switch ($item->getType()) {

            case PluginDporegisterProcessing::class:

                self::showForProcessing($item);
                break;
        }

        return true;
    }

    /**
     * Show the tab content for the Processing Object
     * 
     * @param   PluginDporegisterProcessing $processing
     * 
     * @return  void
     */
    static function showForProcessing(PluginDporegisterProcessing $processing)
    {
        global $DB, $CFG_GLPI;

        $table = self::getTable();

        $processingId = $processing->fields['id'];

        $canedit = PluginDporegisterProcessing::canUpdate();
        $rand = mt_rand();

        if ($canedit) {

            echo "<script type='text/javascript' >\n";
            echo "function viewAddSecurityMesure" . $processingId . "_$rand() {\n";
            $params = [
                'id' => -1,
                'processings_id' => $processingId
            ];

            Ajax::updateItemJsCode(
                "viewsecuritymesure" . $processingId . "_$rand",
                "../ajax/processing_securitymesure_view_subitem.php",
                $params
            );

            echo "$('#viewAddSecurityMesure').hide();";

            echo "};";
            echo "</script>\n";

            echo "<div class='center firstbloc'>";
            echo "<div id='viewsecuritymesure" . $processingId . "_$rand'></div>";
            echo "<a class='vsubmit' id='viewAddSecurityMesure' href='javascript:viewAddSecurityMesure" . $processingId . "_$rand();'>" .
                __('Add a new Security Mesure', 'dporegister') . "</a>";
            echo "</div>";
        }

        $query = "SELECT `".PluginDporegisterSecurityMesure::getTable()."`.*, `$table`.id AS 'IDD',
            `$table`.description
            FROM `".PluginDporegisterSecurityMesure::getTable().
            "` LEFT JOIN `$table` ON `".PluginDporegisterSecurityMesure::getTable()."`.id = `$table`.securitymesures_id
            WHERE `$table`.processings_id = $processingId";

        $result = $DB->query($query);

        if ($result) {

            $number = $DB->numrows($result);

            echo "<div class='spaced'>";
            if ($canedit && $number) {
                Html::openMassiveActionsForm('mass' . __class__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __class__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";

            $header_begin = "<tr>";
            $header_top = '';
            $header_bottom = '';
            $header_end = '';
            if ($canedit && $number) {

                $header_top .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_top .= "</th>";
                $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_bottom .= "</th>";
            }

            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "<th>" . __('Description') . "</th>";
            $header_end .= "<th>" . __('Comment') . "</th>";
            echo $header_begin . $header_top . $header_end . "</tr>";

            while ($data = $DB->fetch_assoc($result)) {

                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__class__, $data["IDD"]);
                    echo "</td>";
                }

                echo "<td width='30%' class='center'" . ($canedit ?
                    "style='cursor:pointer' onClick=\"viewEditSecurityMesure" . $processingId . "_" . $data['IDD'] . "_$rand()\""
                    : '')
                    . ">" . $data['name'] ;

                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditSecurityMesure" . $processingId . "_" . $data['IDD'] . "_$rand() {\n";

                    $params = [
                        'processings_id' => $processingId,
                        'id' => $data["IDD"]
                    ];

                    Ajax::updateItemJsCode(
                        "viewsecuritymesure" . $processingId . "_$rand",
                        "../ajax/processing_securitymesure_view_subitem.php",
                        $params
                    );

                    echo "$('#viewEditSecurityMesure').show();";

                    echo "};";
                    echo "</script>\n";
                }
    
                echo "</td>";
                echo "<td class='center'>" . $data['description'] . "</td>";
                echo "<td class='center'>" . $data['comment'] . "</td>";

                echo "</tr>";
            }

            echo "</table>";

            if ($canedit && $number) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }

            echo "</div>";
        }
    }

    //! @copydoc CommonGLPI::getTabNameForItem($item, $withtemplate)
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginDporegisterProcessing::class:

                $nb = 0;

                if ($_SESSION['glpishow_count_on_tabs']) {

                    $nb = countElementsInTable(
                        self::getTable(),
                        ['processings_id' => $item->getID()]
                    );
                }

                return self::createTabEntry(PluginDporegisterSecurityMesure::getTypeName($nb), $nb);
        }

        return '';
    }

    //! @copydoc CommonDBTM::getForbiddenStandardMassiveAction()
    function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';

        return $forbidden;
    }

    /**
     * Show the current (or new) object formulaire
     * 
     * @param Integer $ID
     * @param Array $options
     */
    function showForm($ID, $options = [])
    {
        $processingId = $options['processings_id'];

        if($ID > 0) {
            $this->getFromDB($ID);
        }

        $colsize1 = '13';
        $colsize2 = '29';
        $colsize3 = '13';
        $colsize4 = '45';

        if (!PluginDporegisterProcessing::canView()) {
            return false;
        }

        $canedit = PluginDporegisterProcessing::canUpdate();

        echo "<div class='firstbloc'>";
        echo "<form name='ticketitem_form' id='ticketitem_form' method='post'
            action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'>";
        echo "<th colspan='4'>" . (($ID < 1) ? __('Add a Security Mesure', 'dporegister') : __('Edit a Security Mesure', 'dporegister')) .
            "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='left' width='$colsize1%'><label>" . __('Security Mesure', 'dporegister') . "</label></td><td width='$colsize2%'>";
        PluginDporegisterSecurityMesure::dropdown([
            'name' => 'securitymesures_id',
            'value' => (array_key_exists('securitymesures_id', $this->fields) ? $this->fields['securitymesures_id'] : '')
        ]);
        echo "<td class='left' width='$colsize1%'><label>" . __('Description') . "</label></td><td width='$colsize2%'>";
        echo "<textarea style='width:98%' maxlength=250 name='description'>" . (array_key_exists('description', $this->fields) ? $this->fields['description'] : '');
        echo "</textarea></td></tr>";

        echo "<tr><td class='center' colspan='4'>";
        echo "<input type='hidden' name='processings_id' value='$processingId' />";

        if ($ID > 0) {
            echo "<input type='hidden' name='id' value='$ID' />";
        }

        echo "<input type='submit' name='" . ($ID < 1 ? 'add' : 'update') . "' value=\"" .
            _sx('button', ($ID < 1 ? 'Add' : 'Update')) . "\" class='submit'>";
        echo "</td></tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";            
    }    
}