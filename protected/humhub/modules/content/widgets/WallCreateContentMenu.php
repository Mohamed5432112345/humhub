<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\content\widgets;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\widgets\stream\WallStreamEntryWidget;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\ui\menu\widgets\Menu;
use Yii;

/**
 * WallCreateContentMenu is the widget for Menu above wall create content Form
 *
 * @property-read ContentContainerActiveRecord $contentContainer
 * @author luke
 * @since 1.13.0
 */
class WallCreateContentMenu extends Menu
{
    /**
     * @inheritdoc
     */
    public $id = 'contentFormMenu';

    /**
     * @inheritdoc
     */
    public $jsWidget = 'content.form.CreateFormMenu';

    /**
     * @inheritdoc
     */
    public $init = true;

    /**
     * @inheritdoc
     */
    public $template = 'wallCreateContentMenu';

    /**
     * @var int What first menu entries should be visible as tabs top level, rest entries will be grouped into sub-menu
     */
    public $visibleEntriesNum = 2;

    /**
     * @var WallCreateContentForm|null
     */
    public $form;

    /**
     * @var bool Visible by default depending on property `$this->form->displayMenu`
     */
    public $isVisible;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->isVisible === null) {
            $this->isVisible = ($this->form instanceof WallCreateContentForm) && $this->form->displayMenu;
        }

        if (!$this->isVisible) {
            return;
        }

        $this->initEntries();

        // Make this widget visible only when two and more entries
        $this->isVisible = count($this->entries) > 1;
    }

    private function initEntries()
    {
        if (!$this->contentContainer) {
            return;
        }

        foreach (Yii::$app->moduleManager->getContentClasses($this->contentContainer) as $i => $contentClass) {
            $content = new $contentClass;
            if (!($content instanceof ContentActiveRecord)) {
                continue;
            }

            $wallEntryWidget = $content->getWallEntryWidget();
            if (!($wallEntryWidget instanceof WallStreamEntryWidget)) {
                continue;
            }

            if (!$content->content->canEdit()) {
                return;
            }

            $menuOptions = [
                'label' => $content->getContentName(),
                'icon' => $wallEntryWidget->menuIcon,
                'url' => '#',
                'sortOrder' => $wallEntryWidget->menuSortOrder,
                'isActive' => $i === 0,
            ];

            switch ($wallEntryWidget->editMode) {
                case WallStreamEntryWidget::EDIT_MODE_INLINE:
                    $menuOptions['htmlOptions'] = [
                        'data-action-click' => $wallEntryWidget->menuAction ?? 'loadForm',
                        'data-action-url' => $this->contentContainer->createUrl($wallEntryWidget->createRoute),
                    ];
                    break;
                case WallStreamEntryWidget::EDIT_MODE_MODAL:
                    $menuOptions['htmlOptions'] = [
                        'data-action-click' => $wallEntryWidget->menuAction ?? 'ui.modal.post',
                        'data-action-url' => $this->contentContainer->createUrl($wallEntryWidget->createRoute),
                    ];
                    break;
                case WallStreamEntryWidget::EDIT_MODE_NEW_WINDOW:
                    $menuOptions['url'] = $this->contentContainer->createUrl($wallEntryWidget->createRoute);
                    break;
                default:
                    $menuOptions = false;
                    break;
            }

            if ($menuOptions) {
                $this->addEntry(new MenuLink($menuOptions));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->isVisible ? parent::run() : '';
    }

    public function getContentContainer(): ?ContentContainerActiveRecord
    {
        return isset($this->form, $this->form->contentContainer) &&
            $this->form instanceof WallCreateContentForm &&
            $this->form->contentContainer instanceof ContentContainerActiveRecord
            ? $this->form->contentContainer
            : null;
    }
}