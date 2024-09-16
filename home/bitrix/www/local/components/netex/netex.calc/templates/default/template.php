<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<script src="/local/templates/aspro_next/js/vue.js"></script>

<div id="app">
    <div class="sections hover_shine">
        <div class="sections__item"
             v-for="(section, index) in sections">
            <div class="section"
                 v-bind:class="{ 'section--active': section.active }"
                 v-on:click="selectSection(index)">
                <div class="section__img-wrap shine">
                    <img class="section__img" v-bind:src="section.img" v-bind:alt="section.name">
                </div>
                <h3 class="section__title">
                    {{ section.name }}
                </h3>
            </div>
        </div>
    </div>
    <div id="properties" class="select-properties">
        <div class="select-properties__item"
             v-for="key in show">
            <div class="property">
                <h5 class="property__title">
                    Выберите {{ properties[key].NAME.toLowerCase() }}:
                </h5>
                <div class="property__values">
                    <div class="property__value"
                         v-for="id in Object.keys(properties[key].VALUES)">
                        <label class="radio-block">
                            <input type="radio"
                                   class="radio-block__input"
                                   v-bind:name="key"
                                   v-bind:value="properties[key].VALUES[id].VALUE"
                                   v-model="selectedProperties[key]"
                                   v-on:change="getNextProperty(key, properties[key].VALUES[id].VALUE)" />
                            <span class="radio-block__picture">
                                <span class="radio-block__icon"></span>
                            </span>
                            <span class="radio-block__text">
                                {{ properties[key].VALUES[id].VALUE.toCapitalize() }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-if="typeof result.sectionsId !== 'undefined'">
        <div class="user-data">
            <h3 class="user-data__title">
                Площадь поверхности для облицовки:
            </h3>
            <div class="user-data__inputs">
                <div class="form-group"
                     v-for="(parameter, index) in parameters">
                    <input class="form-group__input"
                           type="text"
                           required
                           v-bind:id="'input-' + parameter.code"
                           v-bind:name="parameter.code"
                           v-model="parameter.value"
                           v-on:input="function(event){changeParameterValue(event, index)}" />
                    <label class="form-group__label" v-bind:for="'input-' + parameter.code">
                        {{ parameter.name }}
                    </label>
                </div>
                <button class="user-data__submit"
                        type="button"
                        v-on:click="calculateQuantity()">
                    Рассчитать
                </button>
            </div>
        </div>
        <div class="products">
            <div class="products__section"
                 v-for="section in result.sectionsId">
                <h3 class="products__title">
                    {{ result.sections[section].NAME }}
                </h3>
                <div class="products__block hover_shine">
                    <div class="products__item"
                         v-for="item in result.sections[section].ITEMS">
                        <div class="product"
                             v-bind:data-id="item">
                            <a class="product__link shine"
                               v-bind:href="result.items[item].DETAIL_PAGE_URL">
                                <img class="product__img"
                                     v-bind:src="result.items[item].PREVIEW_PICTURE_SRC"
                                     v-bind:alt="result.items[item].NAME">
                            </a>
                            <div class="product__wrap">
                                <div class="product__info">
                                    <h5 class="product__title">
                                        <a class="product__title-link"
                                           v-bind:href="result.items[item].DETAIL_PAGE_URL">
                                            {{ result.items[item].NAME }}
                                        </a>
                                    </h5>
                                    <p class="product__text">
                                        {{ result.items[item].PREVIEW_TEXT }}
                                    </p>
                                </div>
                                <div class="product__offers"
                                     v-if="result.items[item].OFFERS">
                                    <div class="product__offer-item"
                                         v-for="offer in result.items[item].OFFERS">
                                        <div class="product-offer">
                                            <span class="product-offer__size" v-if="result.offers[offer].SIZE.PROPERTY_SIZES_VALUE">
                                                {{ result.offers[offer].SIZE.PROPERTY_SIZES_VALUE }} кг
                                            </span>
                                            <span class="product-offer__size" v-if="result.offers[offer].SIZE.PROPERTY_VOLUME_VALUE">
                                                {{ result.offers[offer].SIZE.PROPERTY_VOLUME_VALUE }} л
                                            </span>
                                            <span class="product-offer__price">
                                                {{ (+result.offers[offer].PRICE).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") }} руб./шт
                                            </span>
                                            <div class="product-offer__counter counter_block"
                                                 v-if="!result.items[item].IN_BASKET">
                                                <span class="minus"
                                                      v-on:click="decrementValue(offer)">-</span>
                                                <input type="text"
                                                       class="text"
                                                       name="quantity"
                                                       v-model="result.offers[offer].VALUE">
                                                <span class="plus"
                                                      v-on:click="incrementValue(offer)">+</span>
                                            </div>
                                            <span class="product-offer__button product-offer__button--wish"
                                                  title="Отложить"
                                                  v-bind:class="{ 'product-offer__button--active': result.offers[offer].WISH }"
                                                  v-on:click="toggleWish(item, offer)">
                                                <i></i>
                                            </span>
                                            <span class="product-offer__button product-offer__button--compare"
                                                  title="В сравнение"
                                                  v-bind:class="{ 'product-offer__button--active': result.offers[offer].COMPARE }"
                                                  v-on:click="toggleCompare(item, offer)">
                                                <i></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="product__offer-item">
                                        <div class="product-offer">
                                            <div class="product__cost"
                                                 v-if="getItemPrice(item)">
                                                <span>Итого: </span>
                                                {{ getItemPrice(item) }} руб.
                                            </div>
                                            <button class="product__offer-buy"
                                                    type="button"
                                                    v-if="!result.items[item].IN_BASKET"
                                                    v-on:click="addToBasket(item)">
                                                Купить
                                            </button>
                                            <a class="product__offer-buy product__offer-buy--in"
                                               href="/basket/"
                                               v-else>
                                                <i></i>
                                                В корзине
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="product__offers"
                                     v-else>
                                    <div class="product__offer-item">
                                        <div class="product-offer">
                                            <span class="product-offer__price product-offer__price--full-width">
                                                {{ (+result.items[item].PRICE).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") }} руб./шт
                                            </span>
                                            <div class="product-offer__counter counter_block"
                                                 v-if="!result.items[item].IN_BASKET">
                                                <span class="minus"
                                                      v-on:click="decrementValue(item)">-</span>
                                                <input type="text"
                                                       class="text"
                                                       name="quantity"
                                                       v-model="result.items[item].VALUE">
                                                <span class="plus"
                                                      v-on:click="incrementValue(item)">+</span>
                                            </div>
                                            <span class="product-offer__button product-offer__button--wish"
                                                  title="Отложить"
                                                  v-bind:class="{ 'product-offer__button--active': result.items[item].WISH }"
                                                  v-on:click="toggleWish(item, item)">
                                                <i></i>
                                            </span>
                                            <span class="product-offer__button product-offer__button--compare"
                                                  title="В сравнение"
                                                  v-bind:class="{ 'product-offer__button--active': result.items[item].COMPARE }"
                                                  v-on:click="toggleCompare(item, item)">
                                                <i></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="product__offer-item">
                                        <div class="product-offer">
                                            <div class="product__cost"
                                                 v-if="getItemPrice(item)">
                                                <span>Итого: </span>
                                                {{ getItemPrice(item) }} руб.
                                            </div>
                                            <button class="product__offer-buy"
                                                    type="button"
                                                    v-if="!result.items[item].IN_BASKET"
                                                    v-on:click="addToBasket(result.items[item].ID)">
                                                Купить
                                            </button>
                                            <a class="product__offer-buy product__offer-buy--in"
                                               href="/basket/"
                                               v-else>
                                                <i></i>
                                                В корзине
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="products__or">или</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    String.prototype.toCapitalize = function() {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

    var properties = <?=\CUtil::PhpToJSObject($arResult["PROPERTIES"])?>

    var propertiesName = {
        appArea: 'oblast_prim',
        substrPlast: 'tip_osnovaniya_pod_shtukaturku',
        finishOutPutty: 'finishnoe_pokrytie_shpatlevki_snaruzhi_pomeshchen',
        substrOutPutty: 'tip_osnovaniya_pod_shpatlevku_snaruzhi_pomeshchen',
        finishInPutty: 'finishnoe_pokrytie_shpatlevki_vnutri_pomeshcheniya',
        substrInPutty: 'tip_osnovaniya_pod_shpatlevku_vnutri_pomeshcheniya',
        typeCoat: 'vid_pokrytiya',
        tileFormat: 'format_plitki',
        finishCoatFloor: 'finishnoe_pokrytie_dlya_nalivnogo_pola',
        thicknessFloor: 'tolshchina_sloya_nalivnogo_pola',
        wallType: 'tip_steny',
        surfaceView: 'vid_poverkhnosti',
        conditionType: 'tip_usloviy',
        kingRoom: 'vid_pomeshcheniya'
    };

    var app = new Vue({
        el: '#app',
        data: {
            activeSection: -1,
            sections: [
                {
                    name: 'Наливные полы и стяжки',
                    img: '/upload/calculator/1.jpg',
                    active: false
                },
                {
                    name: 'Облицовка плиткой',
                    img: '/upload/calculator/2.jpg',
                    active: false
                },
                {
                    name: 'Шпатлевки внутри помещения',
                    img: '/upload/calculator/3.jpg',
                    active: false
                },
                {
                    name: 'Фасадные шпатлевки',
                    img: '/upload/calculator/4.jpg',
                    active: false
                },
                {
                    name: 'Штукатурки',
                    img: '/upload/calculator/5.jpg',
                    active: false
                }
            ],
            properties: properties,
            propertiesId: Object.keys(properties),
            show: [],
            showCount: 0,
            parameters: [
                {
                    code: 'length',
                    name: 'Длина, м',
                    value: ''
                },
                {
                    code: 'width',
                    name: 'Ширина, м',
                    value: ''
                },
                {
                    code: 'depth',
                    name: 'Толщина слоя, мм',
                    value: ''
                },
            ],
            selectedProperties: {},
            result: {}
        },
        methods: {
            selectSection: function (section) {
                var active = this.activeSection;
                if (active !== -1)
                    Vue.set(this.sections[active], 'active', false);
                Vue.set(this.sections[section], 'active', true);
                this.activeSection = section;
                switch (this.activeSection) {
                    case 0:
                    case 1:
                    case 4:
                        this.show = [propertiesName.appArea];
                        break;
                    case 2:
                        this.show = [propertiesName.conditionType];
                        break;
                    case 3:
                        this.show = [propertiesName.substrOutPutty];
                        break;
                    default:
                        this.show = [];
                }
                this.selectedProperties = {};
                this.showCount = 0;
                this.result = {};
                if (window.matchMedia('(max-width: 768px)').matches) {
                    var offset = $("#properties").offset().top - 50;
                    $('body,html').animate({scrollTop: offset}, 500);
                }
            },
            getNextProperty: function (property, value) {
                switch (this.activeSection) {
                    case 0:
                        this.getFirstSection(property, value);
                        break;
                    case 1:
                        this.getSecondSection(property, value);
                        break;
                    case 2:
                        this.getThirdSection(property);
                        break;
                    case 3:
                        this.getFourthSection(property);
                        break;
                    case 4:
                        this.getFifthSection(property, value);
                        break;
                    default:
                        break;
                }
                this.result = {};
            },
            deleteSteps: function (count) {
                while (this.show.length > count) {
                    if (this.show.length === 0) break;
                    delete this.selectedProperties[this.show.pop()];
                }
            },
            getFirstSection: function (property, value) {
                switch (property) {
                    case propertiesName.finishCoatFloor:
                        this.getProducts();
                        break;
                    case propertiesName.thicknessFloor:
                        this.deleteSteps(this.showCount);
                        this.show.push(propertiesName.finishCoatFloor);
                        break;
                    case propertiesName.conditionType:
                        this.deleteSteps(3);
                        this.show.push(propertiesName.thicknessFloor);
                        break;
                    case propertiesName.kingRoom:
                        this.deleteSteps(2);
                        if (value === 'Отапливаемое') {
                            this.show.push(propertiesName.conditionType);
                            this.showCount = 4;
                        } else {
                            this.show.push(propertiesName.thicknessFloor);
                            this.showCount = 3;
                        }
                        break;
                    default:
                        this.deleteSteps(1);
                        if (value === 'для внутренних работ') {
                            this.show.push(propertiesName.kingRoom);
                            this.showCount = 3;
                        } else {
                            this.show.push(propertiesName.thicknessFloor);
                            this.showCount = 2;
                        }
                        break;
                }
            },
            getSecondSection: function (property, value) {
                switch (property) {
                    case propertiesName.typeCoat:
                        this.getProducts();
                        break;
                    case propertiesName.tileFormat:
                        this.deleteSteps(this.showCount);
                        this.show.push(propertiesName.typeCoat);
                        break;
                    case propertiesName.tileFormat:
                        break;
                    case propertiesName.wallType:
                        this.deleteSteps(3);
                        this.show.push(propertiesName.tileFormat);
                        this.showCount = 4;
                        break;
                    case propertiesName.surfaceView:
                        this.deleteSteps(2);
                        if (this.selectedProperties[propertiesName.appArea] !== 'для внутренних работ'
                            && value === 'Стена')
                            this.show.push(propertiesName.wallType);
                        else
                            this.show.push(propertiesName.tileFormat);
                        this.showCount = 3;
                        break;
                    default:
                        this.deleteSteps(1);
                        this.show.push(propertiesName.surfaceView);
                        break;
                }
            },
            getThirdSection: function (property) {
                switch (property) {
                    case propertiesName.finishInPutty:
                        this.getProducts();
                        break;
                    case propertiesName.substrInPutty:
                        this.deleteSteps(2);
                        this.show.push(propertiesName.finishInPutty);
                        break;
                    default:
                        this.deleteSteps(1);
                        this.show.push(propertiesName.substrInPutty);
                        break;
                }
            },
            getFourthSection: function (property) {
                switch (property) {
                    case propertiesName.finishOutPutty:
                        this.getProducts();
                        break;
                    default:
                        this.deleteSteps(1);
                        this.show.push(propertiesName.finishOutPutty);
                        break;
                }
            },
            getFifthSection: function (property, value) {
                switch (property) {
                    case propertiesName.substrPlast:
                        this.getProducts();
                        break;
                    case propertiesName.conditionType:
                        this.deleteSteps(this.showCount);
                        this.show.push(propertiesName.substrPlast);
                        break;
                    case propertiesName.wallType:
                        this.deleteSteps(2);
                        this.show.push(propertiesName.conditionType);
                        break;
                    case propertiesName.kingRoom:
                        this.deleteSteps(2);
                        this.showCount = 3;
                        if (value === 'Отапливаемое')
                            this.show.push(propertiesName.conditionType);
                        else
                            this.show.push(propertiesName.substrPlast);
                        break;
                    default:
                        this.deleteSteps(1);
                        if (value === 'для внутренних работ') {
                            this.show.push(propertiesName.kingRoom);
                            this.showCount = 4;
                        } else {
                            this.show.push(propertiesName.wallType);
                            this.showCount = 3;
                        }
                        break;
                }
            },
            getProducts: function () {
                var $this = this;
                $.ajax({
                    method: 'POST',
                    url: "/local/components/netex/netex.calc/ajax.php",
                    dataType: 'json',
                    data: this.selectedProperties,
                    success: function (data) {
                        if (data) {

                            $.ajax({
                                type: "GET",
                                url: "/ajax/actualBasket.php",
                                data: {
                                    "iblockID": 19
                                },
                                success: function (result) {
                                    var basket = {};
                                    try {
                                        basket = JSON.parse(result.match(/{.+}/)[0].replace(/'/g, '"'));
                                    } catch (err) {
                                        basket = {err: true};
                                    }
                                    if (typeof basket.err === 'undefined') {
                                        for (var item in basket.COMPARE) {
                                            if (typeof data.ITEMS[item] !== 'undefined')
                                                data.ITEMS[item].COMPARE = true;
                                            if (typeof data.OFFERS[item] !== 'undefined')
                                                data.OFFERS[item].COMPARE = true;
                                        }
                                        for (var item in basket.DELAY) {
                                            if (typeof data.ITEMS[item] !== 'undefined')
                                                data.ITEMS[item].WISH = true;
                                            if (typeof data.OFFERS[item] !== 'undefined')
                                                data.OFFERS[item].WISH = true;
                                        }
                                    }
                                    for (var id in data.ITEMS) {
                                        if (typeof data.ITEMS[id].OFFERS === 'undefined') continue;
                                        data.ITEMS[id].OFFERS = data.ITEMS[id].OFFERS.sort(function (a, b) {
                                            if (!!data.OFFERS[a].SIZE.PROPERTY_SIZES_VALUE && !!data.OFFERS[b].SIZE.PROPERTY_SIZES_VALUE) {
                                                if (+data.OFFERS[a].SIZE.PROPERTY_SIZES_VALUE > +data.OFFERS[b].SIZE.PROPERTY_SIZES_VALUE)
                                                    return 1;
                                                if (+data.OFFERS[a].SIZE.PROPERTY_SIZES_VALUE < +data.OFFERS[b].SIZE.PROPERTY_SIZES_VALUE)
                                                    return -1;
                                            }
                                            if (!!data.OFFERS[a].SIZE.PROPERTY_VOLUME_VALUE && !!data.OFFERS[b].SIZE.PROPERTY_VOLUME_VALUE) {
                                                if (+data.OFFERS[a].SIZE.PROPERTY_VOLUME_VALUE > +data.OFFERS[b].SIZE.PROPERTY_VOLUME_VALUE)
                                                    return 1;
                                                if (+data.OFFERS[a].SIZE.PROPERTY_VOLUME_VALUE < +data.OFFERS[b].SIZE.PROPERTY_VOLUME_VALUE)
                                                    return -1;
                                            }
                                            return 0;
                                        });
                                    }
                                    Vue.set($this.result, 'items', data.ITEMS);
                                    Vue.set($this.result, 'offers', data.OFFERS);
                                    Vue.set($this.result, 'sections', data.SECTIONS);
                                    Vue.set($this.result, 'sectionsId', Object.keys(data.SECTIONS));
                                }
                            });
                        }
                    },
                    error: function (xhr, status, err) {
                        console.log(xhr, status, err);
                    }
                });
            },
            changeParameterValue: function (event, index) {
                Vue.set(this.parameters[index], 'value', event.target.value.replace(/,/g, '.').replace(/[^(0-9|\.)]/g, ''));
            },
            decrementValue: function (id) {
                if (typeof this.result.offers[id] !== 'undefined') {
                    if (+this.result.offers[id].VALUE > 1)
                        Vue.set(this.result.offers[id], 'VALUE', +this.result.offers[id].VALUE - 1);
                    else
                        Vue.set(this.result.offers[id], 'VALUE', 1);
                    return;
                }
                if (typeof this.result.items[id] !== 'undefined') {
                    if (+this.result.items[id].VALUE > 1)
                        Vue.set(this.result.items[id], 'VALUE', +this.result.items[id].VALUE - 1);
                    else
                        Vue.set(this.result.items[id], 'VALUE', 1);
                }
            },
            incrementValue: function (id) {
                if (typeof this.result.offers[id] !== 'undefined') {
                    Vue.set(this.result.offers[id], 'VALUE', +this.result.offers[id].VALUE + 1);
                    return;
                }
                if (typeof this.result.items[id] !== 'undefined') {
                    Vue.set(this.result.items[id], 'VALUE', +this.result.items[id].VALUE + 1);
                }
            },
            calculateQuantity: function () {
                var hasError = false;
                var $this = this;
                if (this.parameters[0].value === '') {
                    $('#input-' + this.parameters[0].code).parent().addClass('form-group--error');
                    setTimeout(function () {
                        $('#input-' + $this.parameters[0].code).parent().removeClass('form-group--error');
                    }, 500);
                    hasError = true;
                }
                if (this.parameters[1].value === '') {
                    $('#input-' + this.parameters[1].code).parent().addClass('form-group--error');
                    setTimeout(function () {
                        $('#input-' + $this.parameters[1].code).parent().removeClass('form-group--error');
                    }, 500);
                    hasError = true;
                }
                if (this.parameters[2].value === '') {
                    $('#input-' + this.parameters[2].code).parent().addClass('form-group--error');
                    setTimeout(function () {
                        $('#input-' + $this.parameters[2].code).parent().removeClass('form-group--error');
                    }, 500);
                    hasError = true;
                }

                if (hasError) return;

                var items = Object.keys(this.result.items);
                var quantity = [];
                switch (this.activeSection) {
                    case 0:
                    case 1:
                        for (var i = 0; i < items.length; i++) {
                            if (!this.result.items[items[i]].PROPERTY_RASHOD_KG_VALUE
                                || isNaN(parseFloat(this.result.items[items[i]].PROPERTY_RASHOD_KG_VALUE))) {
                                quantity.push(0);
                                continue;
                            }
                            quantity.push(this.calculateItemQuantity(items[i], false));
                        }
                    case 2:
                    case 3:
                    case 4:
                        for (var i = 0; i < items.length; i++) {
                            if (!this.result.items[items[i]].PROPERTY_RASHOD_KG_VALUE
                                || isNaN(parseFloat(this.result.items[items[i]].PROPERTY_RASHOD_KG_VALUE))) {
                                quantity.push(0);
                                continue;
                            }
                            quantity.push(this.calculateItemQuantity(items[i], true));
                        }
                        break;
                    default:
                        return;
                }
                for (var i = 0; i < quantity.length; i++) {
                    if (quantity[i] === 0) continue;
                    if (typeof this.result.items[items[i]].OFFERS !== 'undefined') {
                        this.calculateOffersQuantity(quantity[i], this.result.items[items[i]].OFFERS);
                        continue;
                    }
                    if (this.result.items[items[i]].PROPERTY_QUANTITY_IN_PACKAGE_VALUE
                        && !isNaN(parseFloat(this.result.items[items[i]].PROPERTY_QUANTITY_IN_PACKAGE_VALUE)))
                        Vue.set(
                            this.result.items[items[i]],
                            'VALUE',
                            Math.ceil(quantity[i] / parseFloat(this.result.items[items[i]].PROPERTY_QUANTITY_IN_PACKAGE_VALUE))
                        );
                }
            },
            calculateItemQuantity: function (id, needCond) {
                return !needCond || this.result.items[id].IBLOCK_SECTION_ID !== '83' ?
                    Math.ceil(
                        +this.parameters[0].value
                        * +this.parameters[1].value
                        * +this.parameters[2].value
                        * parseFloat(this.result.items[id].PROPERTY_RASHOD_KG_VALUE)
                    ) :
                    Math.ceil(
                        +this.parameters[0].value
                        * +this.parameters[1].value
                        * parseFloat(this.result.items[id].PROPERTY_RASHOD_KG_VALUE)
                    );
            },
            calculateOffersQuantity: function (quantity, offers) {
                var sizes = [];
                for (var i = 0; i < offers.length; i++) {
                    if (!!this.result.offers[offers[i]].SIZE.PROPERTY_SIZES_VALUE) {
                        sizes.unshift([
                            +this.result.offers[offers[i]].SIZE.PROPERTY_SIZES_VALUE,
                            +(+this.result.offers[offers[i]].PRICE).toFixed(0),
                            offers[i]
                        ]);
                        continue;
                    }
                    if (!!this.result.offers[offers[i]].SIZE.PROPERTY_VOLUME_VALUE) {
                        sizes.unshift([
                            +this.result.offers[offers[i]].SIZE.PROPERTY_VOLUME_VALUE,
                            +(+this.result.offers[offers[i]].PRICE).toFixed(0),
                            offers[i]
                        ]);
                        continue;
                    }
                    sizes.unshift(0);
                }

                var options = [this.getFirstOption(quantity, sizes[0][0], sizes.length)];
                for (var i = 1; i < sizes.length; i++) {
                    var tempOptions = [];
                    for (var j = 0; j < options.length; j++) {
                        tempOptions = tempOptions.concat(generateOptions(
                            options[j].slice(0),
                            options[j].slice(0),
                            i - 1,
                            [],
                            quantity,
                            sizes
                        ));
                    }
                    options = options.concat(tempOptions);
                }

                var bestOffer = this.findMinPrice(options, sizes);
                for (var i = 0; i < sizes.length; i++) {
                    Vue.set(
                        this.result.offers[sizes[i][2]],
                        'VALUE',
                        bestOffer[i]
                    );
                }
            },
            findMinPrice: function (options, sizes) {
                var min = [],
                    minValue = Infinity;
                for (var i = 0; i < options.length; i++) {
                    var loopPrice = 0;
                    for (var j = 0; j < sizes.length; j++) {
                        loopPrice += options[i][j] * sizes[j][1];
                    }
                    if (minValue > loopPrice) {
                        minValue = loopPrice;
                        min = options[i];
                    }
                }
                return min;
            },
            getFirstOption: function (quantity, size, count) {
                var option = Array.apply(null, Array(count)).map(Number.prototype.valueOf, 0);
                option[0] = Math.ceil(quantity / size);
                return option;
            },
            getItemPrice: function (id) {
                var price = 0;
                if (typeof this.result.items[id].OFFERS !== 'undefined') {
                    var offers = this.result.items[id].OFFERS;
                    for (var i = 0; i < offers.length; i++) {
                        price += +this.result.offers[offers[i]].VALUE * (+this.result.offers[offers[i]].PRICE).toFixed(0)
                    }
                } else {
                    price = +this.result.items[id].VALUE * (+this.result.items[id].PRICE).toFixed(0)
                }
                return price > 0 ?
                    price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") :
                    0;
            },
            addToBasket: function (id) {
                if (typeof this.result.items[id].OFFERS !== 'undefined') {
                    var back = true;
                    var offers = this.result.items[id].OFFERS;
                    for (var i = 0; i < offers.length; i++) {
                        if (+this.result.offers[offers[i]].VALUE !== 0) {
                            back = false;
                            break;
                        }
                    }
                    if (back) {
                        $('[data-id="' + id + '"] .product-offer__counter').addClass('product-offer__counter--error');
                        setTimeout(function () {
                            $('[data-id="' + id + '"] .product-offer__counter').removeClass('product-offer__counter--error');
                        }, 1000);
                        return;
                    }
                    var success = [];
                    var $this = this;
                    for (var i = 0; i < offers.length; i++) {
                        if (+this.result.offers[offers[i]].VALUE === 0) {
                            success.push(true);
                            continue;
                        }
                        $.ajax({
                            type: "POST",
                            url: "/ajax/item.php",
                            data:{
                                add_item: "Y",
                                add_props: "Y",
                                basket_props: "",
                                iblockID: 19,
                                item: offers[i],
                                offers: "Y",
                                part_props: "Y",
                                prop: [0],
                                props: "''",
                                quantity: this.result.offers[offers[i]].VALUE,
                                rid: ""
                            },
                            dataType:"json",
                            success:function(data){
                                getActualBasket(19);
                                if (data !== null) {
                                    if ("STATUS" in data) {
                                        if (data.MESSAGE_EXT === null)
                                            data.MESSAGE_EXT = '';
                                        if (data.STATUS === 'OK')
                                            success.push(true);
                                        else
                                            showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT);
                                    } else
                                        showBasketError(BX.message("CATALOG_PARTIAL_BASKET_PROPERTIES_ERROR"));
                                } else
                                    success.push(true);
                                if (success.length === offers.length) {
                                    addBasketCounter(id);
                                    Vue.set($this.result.items[id], 'IN_BASKET', true);
                                    for (var j = 0; j < offers.length; j++) {
                                        Vue.set($this.result.offers[offers[j]], 'WISH', false);
                                    }
                                    if ($("#ajax_basket").length)
                                        reloadTopBasket('add', $('#ajax_basket'), 200, 5000, 'Y');
                                    if ($("#basket_line .basket_fly").length) {
                                        if (window.matchMedia('(max-width: 767px)').matches
                                            || $("#basket_line .basket_fly.loaded").length)
                                            basketFly('open', 'N');
                                        else
                                            basketFly('open');
                                    }
                                }
                            }
                        });
                    }
                } else {
                    if (+this.result.items[id].VALUE === 0) {
                        $('[data-id="' + id + '"] .product-offer__counter').addClass('product-offer__counter--error');
                        setTimeout(function () {
                            $('[data-id="' + id + '"] .product-offer__counter').removeClass('product-offer__counter--error');
                        }, 1000);
                        return;
                    }
                    var $this = this;
                    $.ajax({
                        type: "POST",
                        url: "/ajax/item.php",
                        data: {
                            add_item: "Y",
                            add_props: "Y",
                            basket_props: "",
                            iblockID: 19,
                            item: id,
                            offers: "N",
                            part_props: "Y",
                            prop: [0],
                            props: "''",
                            quantity: this.result.items[id].VALUE,
                            rid: ""
                        },
                        dataType: "json",
                        success: function (data) {
                            getActualBasket(19);
                            if (data !== null) {
                                if ("STATUS" in data) {
                                    if (data.MESSAGE_EXT === null)
                                        data.MESSAGE_EXT = '';
                                    if (data.STATUS === 'OK') {
                                        addBasketCounter(id);
                                        Vue.set($this.result.items[id], 'IN_BASKET', true);
                                        Vue.set($this.result.items[id], 'WISH', false);

                                        if ($("#ajax_basket").length)
                                            reloadTopBasket('add', $('#ajax_basket'), 200, 5000, 'Y');
                                        if ($("#basket_line .basket_fly").length) {
                                            if (window.matchMedia('(max-width: 767px)').matches
                                                || $("#basket_line .basket_fly.loaded").length)
                                                basketFly('open', 'N');
                                            else
                                                basketFly('open');
                                        }
                                    }
                                    else
                                        showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT);
                                } else
                                    showBasketError(BX.message("CATALOG_PARTIAL_BASKET_PROPERTIES_ERROR"));
                            } else {
                                addBasketCounter(id);
                                Vue.set(this.result.items[id], 'IN_BASKET', true);
                                Vue.set(this.result.items[id], 'WISH', false);

                                if ($("#ajax_basket").length)
                                    reloadTopBasket('add', $('#ajax_basket'), 200, 5000, 'Y');
                                if ($("#basket_line .basket_fly").length) {
                                    if (window.matchMedia('(max-width: 767px)').matches
                                        || $("#basket_line .basket_fly.loaded").length)
                                        basketFly('open', 'N');
                                    else
                                        basketFly('open');
                                }
                            }
                        }
                    });
                }

            },
            toggleCompare: function (product, item) {
                var $this = this;
                $.get(
                    '/ajax/item.php?item=' + item + '&compare_item=Y&iblock_id=19',
                    function () {
                        if ($('#compare_fly').length)
                            jsAjaxUtil.InsertDataToNode('/ajax/show_compare_preview_fly.php', 'compare_fly', false);
                        if (product === item)
                            Vue.set($this.result.items[item], 'COMPARE', !$this.result.items[item].COMPARE);
                        else
                            Vue.set($this.result.offers[item], 'COMPARE', !$this.result.offers[item].COMPARE);
                    }
                );
            },
            toggleWish: function (product, item) {
                var $this = this;
                if (product === item) {
                    $.ajax({
                        type: "GET",
                        url: "/ajax/item.php",
                        data: "item="+item+"&quantity="+$this.result.items[item].VALUE+"&wish_item=Y&offers=N&iblockID=19&props=''",
                        dataType: "json",
                        success: function (data) {
                            getActualBasket(19);
                            if (data !== null) {
                                if (data.MESSAGE_EXT === null)
                                    data.MESSAGE_EXT = '';
                                if ("STATUS" in data) {
                                    if (data.STATUS === 'OK') {
                                        Vue.set($this.result.items[item], 'WISH', !$this.result.items[item].WISH);

                                        if ($("#ajax_basket").length)
                                            reloadTopBasket('wish', $('#ajax_basket'), 200, 5000, 'N');

                                        if ($("#basket_line .basket_fly").length) {
                                            if(window.matchMedia('(max-width: 767px)').matches
                                                || $("#basket_line .basket_fly.loaded").length)
                                                basketFly('wish', 'N');
                                            else
                                                basketFly('wish');
                                        }
                                    } else
                                        showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT, BX.message("ERROR_ADD_DELAY_ITEM"));
                                } else
                                    showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT, BX.message("ERROR_ADD_DELAY_ITEM"));
                            } else {
                                Vue.set($this.result.items[item], 'WISH', !$this.result.items[item].WISH);

                                if ($("#ajax_basket").length)
                                    reloadTopBasket('wish', $('#ajax_basket'), 200, 5000, 'N');

                                if ($("#basket_line .basket_fly").length) {
                                    if (window.matchMedia('(max-width: 767px)').matches
                                        || $("#basket_line .basket_fly.loaded").length)
                                        basketFly('wish', 'N');
                                    else
                                        basketFly('wish');
                                }
                            }
                        }
                    });
                } else {
                    $.ajax({
                        type: "GET",
                        url: "/ajax/item.php",
                        data: "item="+item+"&quantity="+$this.result.offers[item].VALUE+"&wish_item=Y&offers=Y&iblockID=19&props=''",
                        dataType: "json",
                        success: function (data) {
                            getActualBasket(19);
                            if (data !== null) {
                                if (data.MESSAGE_EXT === null)
                                    data.MESSAGE_EXT = '';
                                if ("STATUS" in data) {
                                    if (data.STATUS === 'OK') {
                                        Vue.set($this.result.offers[item], 'WISH', !$this.result.offers[item].WISH);

                                        if ($("#ajax_basket").length)
                                            reloadTopBasket('wish', $('#ajax_basket'), 200, 5000, 'N');

                                        if ($("#basket_line .basket_fly").length) {
                                            if(window.matchMedia('(max-width: 767px)').matches
                                                || $("#basket_line .basket_fly.loaded").length)
                                                basketFly('wish', 'N');
                                            else
                                                basketFly('wish');
                                        }
                                    } else
                                        showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT, BX.message("ERROR_ADD_DELAY_ITEM"));
                                } else
                                    showBasketError(BX.message(data.MESSAGE)+' <br/>'+data.MESSAGE_EXT, BX.message("ERROR_ADD_DELAY_ITEM"));
                            } else {
                                Vue.set($this.result.offers[item], 'WISH', !$this.result.offers[item].WISH);

                                if ($("#ajax_basket").length)
                                    reloadTopBasket('wish', $('#ajax_basket'), 200, 5000, 'N');

                                if ($("#basket_line .basket_fly").length) {
                                    if (window.matchMedia('(max-width: 767px)').matches
                                        || $("#basket_line .basket_fly.loaded").length)
                                        basketFly('wish', 'N');
                                    else
                                        basketFly('wish');
                                }
                            }
                        }
                    });
                }
            }
        }
    });

    function generateOptions(baseArr, arr, pos, outArr, quantity, sizes) {
        if (!arr.length) return outArr;
        if (arr[pos] > 0) {
            arr[pos] = arr[pos] - 1;
            arr[pos + 1] = calculateNextPos(arr, pos, quantity, sizes);
            outArr.push(arr.slice(0));
            return generateOptions(baseArr.slice(0), arr.slice(0), pos, outArr, quantity, sizes);
        }
        var newItem = getNewArr(baseArr.slice(0), arr.slice(0), arr.slice(0), pos, pos, quantity, sizes);
        if (newItem.length) outArr.push(newItem.slice(0));
        return generateOptions(
            baseArr.slice(0),
            newItem,
            pos,
            outArr,
            quantity,
            sizes
        );
    }

    function getNewArr(baseArr, arr, tempArr, pos, basePos, quantity, sizes) {
        if (pos < 0) return [];
        if (!arr[pos]) {
            arr[pos] = baseArr[pos];
            return getNewArr(
                baseArr.slice(0),
                arr.slice(0),
                tempArr.slice(0, pos).concat(arr.slice(pos)),
                pos - 1,
                basePos,
                quantity,
                sizes
            );
        }
        tempArr[pos] = arr[pos] - 1;
        tempArr[basePos + 1] = calculateNextPos(tempArr, basePos, quantity, sizes);
        return tempArr.slice(0);
    }

    function calculateNextPos(arr, pos, quantity, sizes) {
        for (var i = 0; i <= pos; i++) {
            quantity = quantity - sizes[i][0] * arr[i];
        }
        return Math.ceil(quantity / sizes[pos + 1][0]);
    }
</script>
