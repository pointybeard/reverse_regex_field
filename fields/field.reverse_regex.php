<?php

require_once realpath(__DIR__ . "/../vendor") . "/autoload.php";

use ReverseRegexField\Lib;

class FieldReverse_Regex extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Reverse Regex');
        $this->_required = true;

        $this->set('required', 'no');

        extension_Reverse_Regex_Field::init();
    }

    public function commit()
    {
        if (!parent::commit() || $this->get('id') === false) {
            return false;
        }

        return FieldManager::saveSettings($this->get('id'), [
            'pattern' => $this->get('pattern'),
            'unique' => $this->get('unique'),
        ]);
    }

    public function checkFields(array &$errors, $checkForDuplicates = true)
    {
        parent::checkFields($errors, $checkForDuplicates);

        if (empty($this->get('pattern'))) {
            $errors['pattern'] = __('This is a required field.');
        }

        return (
            !empty($errors)
                ? self::__ERROR__
                : self::__OK__
        );
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canFilter()
    {
        return true;
    }

    public function canPrePopulate()
    {
        return true;
    }

    public function isSortable()
    {
        return true;
    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    private function generateValueFromPattern()
    {
        $result = null;

        $generator = (new \ReverseRegex\Parser(
            new \ReverseRegex\Lexer($this->get("pattern")),
            new \ReverseRegex\Generator\Scope,
            new \ReverseRegex\Generator\Scope
        ))->parse()->getResult();

        $tries = 1000;

        do {
            $result = "";
            $generator->generate($result, new ReverseRegex\Random\SimpleRandom());
            $tries--;

            // If the unique flag is on, we need to keep regenerating the result
        // until we find something unique. Problem is, if the scope of possible
        // values is too small (due to a limited pattern), we could end up in
        // and endless loop. This will keep testing for uniqueness until either
        // a unique value is found or we hit 1000 tries.
        } while (
            strtolower($this->get("unique")) == 'yes' &&
            $tries > 0 &&
            !$this->isUnique($result)
        );

        if (!$this->isUnique($result)) {
            throw new Lib\Exceptions\CouldNotFindUniqueValueException($this->get("pattern"));
        }

        return $result;
    }

    private function isUnique($value)
    {
        $count = (int)Symphony::Database()->fetchVar(
            'count',
            0,
            sprintf(
                "SELECT COUNT(*) as `count`
                FROM `tbl_entries_data_%d`
                WHERE `value` = '%s'",
                $this->get('id'),
                $value
            )
        );

        return ($count <= 0);
    }

    private function getExistingValue($entryId)
    {
        $existingValue = Symphony::Database()->fetchVar(
            'value',
            0,
            sprintf(
                "SELECT `value`
                FROM `tbl_entries_data_%d`
                WHERE `entry_id` = %d
                LIMIT 1",
                $this->get('id'),
                $entryId
            )
        );

        return $existingValue;
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `value` varchar(36) default null,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        ########### REGEX PATTERN ###########
        $label = Widget::Label(__('Pattern'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][pattern]', $this->get('pattern')));

        if (isset($errors['pattern'])) {
            $div = new XMLElement('div');
            $div->appendChild($label);
            $wrapper->appendChild(Widget::Error($div, $errors['pattern']));
        } else {
            $wrapper->appendChild($label);
        }

        $wrapper->appendChild(new XMLElement('p', __('Pattern must be a valid regular expressions. See <a href="https://github.com/icomefromthenet/ReverseRegex/tree/v0.0.6.3#regex-support">here for a list of supported patterns</a>. This will also be used to validate input if not auto-generated.'), array('class' => 'help')));
        #################################


        ########### UNIQUENESS CHECKBOX ###########
        $label = Widget::Label();
        $input = Widget::Input('fields['.$this->get('sortorder').'][unique]', 'yes', 'checkbox');
        if ($this->get('unique') == 'yes') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate() . ' ' . __('Only generate unique values'));
        $wrapper->appendChild($label);
        #################################


        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $value = General::sanitize(isset($data['value']) ? $data['value'] : $this->generateValueFromPattern());
        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        // Add the disabled field. It won't make it to the POST data
        $label->appendChild(Widget::Input(
            'reverseRegexField-disabeld',
            (strlen($value) != 0 ? $value : null),
            'text',
            ['disabled' => 'disabled']
        ));

        // Add hidden field with actual value
        $label->appendChild(Widget::Input(
            sprintf(
                'fields%s[%s]%s',
                $fieldnamePrefix,
                $this->get('element_name'),
                $fieldnamePostfix
            ),
            (strlen($value) != 0 ? $value : null),
            'text',
            ['hidden' => 'hidden']
        ));

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;

        if (is_array($data) && isset($data['value'])) {
            $data = $data['value'];
        }

        if (strlen(trim($data)) == 0) {
            $data = $this->generateValueFromPattern();
        }

        // Order ID cannot be changed once it is saved. Look up the existing
        // order ID first and if it's set, use that instead.
        if ($entry_id != null) {
            $existingValue = $this->getExistingValue($entry_id);
            if ($existingValue != null && strlen(trim($existingValue)) > 0) {
                $data = $existingValue;
            }
        }

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (strlen(trim($data)) == 0) {
            $data = $this->generateValueFromPattern();
        }

        // Order ID cannot be changed once it is saved. Look up the existing
        // order ID first and if it's set, use that instead.
        if ($entry_id != null) {
            $existingValue = $this->getExistingValue($entry_id);
            if ($existingValue != null && strlen(trim($existingValue)) > 0) {
                $data = $existingValue;
            }
        }

        $result = [
            'value' => $data
        ];

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $value = $data['value'];

        if ($encode === true) {
            $value = General::sanitize($value);
        } else {
            include_once TOOLKIT . '/class.xsltprocess.php';

            if (!General::validateXML($data['value'], $errors, false, new XsltProcess)) {
                $value = html_entity_decode($data['value'], ENT_QUOTES, 'UTF-8');
                $value = $this->__replaceAmpersands($value);

                if (!General::validateXML($value, $errors, false, new XsltProcess)) {
                    $value = General::sanitize($data['value']);
                }
            }
        }

        $wrapper->appendChild(
            new XMLElement($this->get('element_name'), $value)
        );
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getValue' =>       ImportableField::STRING_VALUE,
            'getPostdata' =>    ImportableField::ARRAY_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if ($mode === $modes->getValue) {
            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Export:
    -------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes()
    {
        return array(
            'getUnformatted' => ExportableField::UNFORMATTED,
            'getPostdata' =>    ExportableField::POSTDATA
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return string|null
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        // Export unformatted:
        if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : null;
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value'), $joins, $where);
        } elseif ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;
                $value = $this->cleanValue($value);
                $joins .= "
                    LEFT JOIN
                        `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                        ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                ";
                $where .= "
                    AND (
                        t{$field_id}_{$this->_key}.value = '{$value}'
                    )
                ";
            }
        } else {
            if (!is_array($data)) {
                $data = array($data);
            }

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $data = implode("', '", $data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND (
                    t{$field_id}_{$this->_key}.value IN ('{$data}')
                )
            ";
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Sorting:
    -------------------------------------------------------------------------*/

    public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC')
    {
        if (in_array(strtolower($order), array('random', 'rand'))) {
            $sort = 'ORDER BY RAND()';
        } else {
            $sort = sprintf(
                'ORDER BY (
                    SELECT %s
                    FROM tbl_entries_data_%d AS `ed`
                    WHERE entry_id = e.id
                ) %s',
                '`ed`.value',
                $this->get('id'),
                $order
            );
        }
    }
}
