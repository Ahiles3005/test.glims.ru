<?php
namespace Netex\Calc\Component;

class Calculator extends \CBitrixComponent
{
    /**
     * Execution of component
     */
    public function executeComponent()
    {
        $query = \CIBlockElement::GetList(
            false,
            [],
            false,
            false,
            ['PROPERTY_maksimalnaya_tolshchina_sloya_nalivnogo_pola_mm', 'PROPERTY_minimalnaya_tolshchina_sloya_nalivnogo_pola_mm']);

        while ($row = $query->Fetch()) {
            if ($row['PROPERTY_MAKSIMALNAYA_TOLSHCHINA_SLOYA_NALIVNOGO_POLA_MM_VALUE']) {
                $arValues['MAKSIMALNAYA_TOLSHCHINA_VALUES'][] = $row['PROPERTY_MAKSIMALNAYA_TOLSHCHINA_SLOYA_NALIVNOGO_POLA_MM_VALUE'];
            }
            if ($row['PROPERTY_MINIMALNAYA_TOLSHCHINA_SLOYA_NALIVNOGO_POLA_MM_VALUE']) {
                $arValues['MINIMALNAYA_TOLSHCHINA_VALUES'][] = $row['PROPERTY_MINIMALNAYA_TOLSHCHINA_SLOYA_NALIVNOGO_POLA_MM_VALUE'];
            }
        }

        $this->arResult['MAX'] = max($arValues['MAKSIMALNAYA_TOLSHCHINA_VALUES']);
        $this->arResult['MIN'] = min($arValues['MINIMALNAYA_TOLSHCHINA_VALUES']);

        $props = [
            'oblast_prim',
            'vid_pomeshcheniya',
            'tip_usloviy',
            'vid_poverkhnosti',
            'finishnoe_pokrytie_dlya_nalivnogo_pola',
            'format_plitki',
            'vid_pokrytiya',
            'tip_osnovaniya_pod_shpatlevku_vnutri_pomeshcheniya',
            'finishnoe_pokrytie_shpatlevki_vnutri_pomeshcheniya',
            'tip_osnovaniya_pod_shpatlevku_snaruzhi_pomeshchen',
            'finishnoe_pokrytie_shpatlevki_snaruzhi_pomeshchen',
            'tip_osnovaniya_pod_shtukaturku'
        ];
        $query = \Bitrix\Iblock\PropertyTable::getList([
            'select' => ['ID', 'NAME', 'CODE'],
            'filter' => ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ACTIVE' => 'Y','CODE' => $props]
        ]);

        while ($row = $query->fetch()) {
            $q = \Bitrix\Iblock\PropertyEnumerationTable::getList([
                'select' => ['ID', 'VALUE', 'PROPERTY_ID'],
                'filter' => ['PROPERTY_ID' => $row['ID']],
                'order' => ['SORT' => 'ASC'],
            ])->fetchAll();

            foreach ($q as $key => $item) {
                $row['VALUES'][$key] = $item;
                $this->arResult['PROPERTIES'][$row['CODE']] = $row;
            }
        }
        $this->includeComponentTemplate();
    }
}