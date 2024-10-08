<?php

namespace Sprint\Migration;

class Version20150520000001 extends Version
{

    protected $description = "Добавляем инфоблок новости";

    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Iblock()->saveIblockType([
            'ID' => 'content',
            'LANG' => [
                'en' => [
                    'NAME' => 'Контент',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
                'ru' => [
                    'NAME' => 'Контент',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы',
                ],
            ],
        ]);

        $iblockId1 = $helper->Iblock()->saveIblock([
            'NAME' => 'Новости',
            'CODE' => 'content_news',
            'IBLOCK_TYPE_ID' => 'content',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '//news/#ELEMENT_ID#',
        ]);

        $helper->Iblock()->saveIblockFields($iblockId1, [
            'CODE' => [
                'DEFAULT_VALUE' => [
                    'TRANSLITERATION' => 'Y',
                    'UNIQUE' => 'Y',
                ],
            ],
        ]);

        $helper->Iblock()->saveProperty($iblockId1, [
            'NAME' => 'Ссылка',
            'CODE' => 'LINK',
        ]);

        $this->outSuccess('Инфоблок создан');

    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $ok = $helper->Iblock()->deleteIblockIfExists('content_news');

        if ($ok) {
            $this->outSuccess('Инфоблок удален');
        } else {
            $this->outError('Ошибка удаления инфоблока');
        }
    }

}
