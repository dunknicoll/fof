<?php
/**
 * @package     FrameworkOnFramework
 * @subpackage  render
 * @copyright   Copyright (C) 2010 - 2014 Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('FOF_INCLUDED') or die;

/**
 * Joomla! 3 view renderer class
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class FOFRenderJoomla3 extends FOFRenderStrapper
{
	/**
	 * Public constructor. Determines the priority of this class and if it should be enabled
	 */
	public function __construct()
	{
		$this->priority	 = 55;
		$this->enabled	 = version_compare(JVERSION, '3.0', 'ge');
	}

	/**
	 * Echoes any HTML to show before the view template
	 *
	 * @param   string    $view    The current view
	 * @param   string    $task    The current task
	 * @param   FOFInput  $input   The input array (request parameters)
	 * @param   array     $config  The view configuration array
	 *
	 * @return  void
	 */
	public function preRender($view, $task, $input, $config = array())
	{
		$format	 = $input->getCmd('format', 'html');

		if (empty($format))
		{
			$format	 = 'html';
		}

		if ($format != 'html')
		{
			return;
		}

		// Render the submenu and toolbar
		if ($input->getBool('render_toolbar', true))
		{
			$this->renderButtons($view, $task, $input, $config);
			$this->renderLinkbar($view, $task, $input, $config);
		}
	}

	/**
	 * Echoes any HTML to show after the view template
	 *
	 * @param   string    $view    The current view
	 * @param   string    $task    The current task
	 * @param   FOFInput  $input   The input array (request parameters)
	 * @param   array     $config  The view configuration array
	 *
	 * @return  void
	 */
	public function postRender($view, $task, $input, $config = array())
	{
		/*
		We don't need to do anything here, if we are running Joomla3,
		so overwrite the default with all the closing div's

		I added it here because I am not 100% sure if it would break BC
		when doing it in the default strapper
		*/
	}

	/**
	 * Renders the submenu (link bar)
	 *
	 * @param   string    $view    The active view name
	 * @param   string    $task    The current task
	 * @param   FOFInput  $input   The input object
	 * @param   array     $config  Extra configuration variables for the toolbar
	 *
	 * @return  void
	 */
	protected function renderLinkbar($view, $task, $input, $config = array())
	{
		$style = 'joomla';

		if (array_key_exists('linkbar_style', $config))
		{
			$style = $config['linkbar_style'];
		}

		switch ($style)
		{
			case 'joomla':
				$this->renderLinkbar_joomla($view, $task, $input);
				break;

			case 'classic':
			default:
				$this->renderLinkbar_classic($view, $task, $input);
				break;
		}
	}

    /**
     * Renders a raw FOFForm and returns the corresponding HTML
     *
     * @param   FOFForm   &$form     The form to render
     * @param   FOFModel  $model     The model providing our data
     * @param   FOFInput  $input     The input object
     * @param   string    $formType  The form type e.g. 'edit' or 'read'
     *
     * @return  string    The HTML rendering of the form
     */
    protected function renderFormRaw(FOFForm &$form, FOFModel $model, FOFInput $input, $formType)
    {
        $html = '';
        $fieldsets = $form->getFieldsets();
        $tabs = $form->getAttribute('tabs',false);
        $viewName = $form->getView()->get('name');

        if (!empty($fieldsets) && $tabs)
        {
            $first_fieldset = reset($fieldsets);
            $first_fieldset = $first_fieldset->name . "_tab";
            $active_fieldset = $form->getAttribute('active-tab', $first_fieldset);
            $html .= JHtml::_('bootstrap.startTabSet', $viewName, array('active' => $active_fieldset));
        }

        foreach ($fieldsets as $fieldset)
        {
            $fields = $form->getFieldset($fieldset->name);

            if ($tabs)
            {
                $html .= JHtml::_('bootstrap.addTab', $viewName, $fieldset->name."_tab", $fieldset->label);
            }

            if (isset($fieldset->class))
            {
                $class = 'class="' . $fieldset->class . '"';
            }
            else
            {
                $class = '';
            }

            $html .= "\t" . '<div id="' . $fieldset->name . '" ' . $class . '>' . PHP_EOL;

            if (isset($fieldset->label) && !empty($fieldset->label) && !$tabs)
            {
                $html .= "\t\t" . '<h3>' . JText::_($fieldset->label) . '</h3>' . PHP_EOL;
            }

            foreach ($fields as $field)
            {
                $required    = $field->required;
                $labelClass  = $field->labelClass;
                $groupClass  = $form->getFieldAttribute($field->fieldname, 'groupclass', '', $field->group);

                // Auto-generate label and description if needed
                // Field label
                $title       = $form->getFieldAttribute($field->fieldname, 'label', '', $field->group);
                $emptylabel  = $form->getFieldAttribute($field->fieldname, 'emptylabel', false, $field->group);

                if (empty($title) && !$emptylabel)
                {
                    $model->getName();
                    $title = strtoupper($input->get('option') . '_' . $model->getName() . '_' . $field->id . '_LABEL');
                }

                // Field description
                $description = $form->getFieldAttribute($field->fieldname, 'description', '', $field->group);

                /**
                 * The following code is backwards incompatible. Most forms don't require a description in their form
                 * fields. Having to use emptydescription="1" on each one of them is an overkill. Removed.
                 */
                /*
                $emptydescription   = $form->getFieldAttribute($field->fieldname, 'emptydescription', false, $field->group);
                if (empty($description) && !$emptydescription)
                {
                    $description = strtoupper($input->get('option') . '_' . $model->getName() . '_' . $field->id . '_DESC');
                }
                */

                if ($formType == 'read')
                {
                    $inputField = $field->static;
                }
                elseif ($formType == 'edit')
                {
                    $inputField = $field->input;
                }

                if (empty($title))
                {
                    $html .= "\t\t\t" . $inputField . PHP_EOL;

                    if (!empty($description) && $formType == 'edit')
                    {
                        $html .= "\t\t\t\t" . '<span class="help-block">';
                        $html .= JText::_($description) . '</span>' . PHP_EOL;
                    }
                }
                else
                {
                    $html .= "\t\t\t" . '<div class="control-group ' . $groupClass . '">' . PHP_EOL;
                    $html .= "\t\t\t\t" . '<label class="control-label ' . $labelClass . '" for="' . $field->id . '">' . PHP_EOL;
                    $html .= "\t\t\t\t" . JText::_($title) . PHP_EOL;

                    if ($required)
                    {
                        $html .= ' *';
                    }

                    $html .= "\t\t\t\t" . '</label>' . PHP_EOL;
                    $html .= "\t\t\t\t" . '<div class="controls">' . PHP_EOL;
                    $html .= "\t\t\t\t" . $inputField . PHP_EOL;

                    if (!empty($description))
                    {
                        $html .= "\t\t\t\t" . '<span class="help-block">';
                        $html .= JText::_($description) . '</span>' . PHP_EOL;
                    }

                    $html .= "\t\t\t\t" . '</div>' . PHP_EOL;
                    $html .= "\t\t\t" . '</div>' . PHP_EOL;
                }
            }

            $html .= "\t" . '</div>' . PHP_EOL;

            if ($tabs)
            {
                $html .= JHtml::_('bootstrap.endTab');
            }
        }

        if (!empty($fieldsets) && $tabs)
        {
            $html .= JHtml::_('bootstrap.endTabSet');
        }

        return $html;
    }
}
