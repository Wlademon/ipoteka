<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Альфа страхование</title>
    <style type="text/css">
        body {
            line-height: 1;
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            width: 730px;
            font-size: 8px;
            color: gray;
        }

        .sample-image {
            z-index: -1;
            position: absolute;
            margin-top: 0;
            margin-left: 0;
            width: 730px;
        }

        .text-white {
            color: white;
            font-weight: bold;
        }

        .text-gray {
            color: gray;
        }

        .main-border-table {
            border: 3px solid gray;
            width: 700px;
        }

        .container-back {
            width: 100%;
            padding-right: 1rem;
            margin-right: auto;
            margin-left: auto;
            z-index: 1;
            position: absolute;
            top: 0;
        }
        .container-back__image {
            width: 730px;
            height: 1100px;
            border: solid 1px black;
        }

        .container-top {
            margin: 0 30px 0 30px;
        }

        .main-table {
            width: 671px;
            height: 926px;
        }

        .inner-table {
            line-height: 9px;
            width: 100%;
            /*border-collapse: collapse;*/
            /*margin-bottom: 4px;*/
        }

        .inner-table td {
            padding: 5px;
            border: 2px solid #838a91 !important;
            margin-left: 2px;
        }

        .inner-table td:first-child {
            border-left: none !important;
        }

        .inner-table td:last-child {
            border-right: none !important;
        }

        .inner-table td.no-border {
            border: none !important;
        }

        .td-dark-gray {
            background: #838a91;
            padding: 5px;
            color: white;
            font-weight: bold;
            border: 2px solid #838a91 !important;
        }

        .td-gray {
            background: #e3e2e6;
            padding: 5px;
            border: 2px solid #838a91 !important;
        }

        .text-center {
            text-align: center;
        }

        .list {
            padding: 0 0 0 20px;
        }

        .list li {
            margin-bottom: 2px;
        }

        .two-page-image {
            width: 730px;
        }

        .police-number {
            position: absolute;
            top: 84px;
            left: 460px;
            font-size: 12px;
        }

        /*# sourceMappingURL=styles.css.map */

    </style>
</head>
<body>
@if($sample)
    <img src="data:image/png;base64, {{ base64_encode(file_get_contents(public_path('/ns/images/sample.png'))) }}" alt="sample"
         style="position: absolute; margin-top: 0px; margin-left: 0px; width: 100%">
@endif
<img class="sample-image" src="data:image/png;base64, {{ base64_encode(file_get_contents(public_path('/alfa/images/back-1-full-cleared.png'))) }}">

<div class="container-top">
    <div class="police-number">{{ $contract->number }}</div>
    <table class="main-table" cellspacing="0">
        <tbody>
        <tr>
            <td style="vertical-align: top; padding-top: 110px;">
                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="no-border">
                            Настоящий страховой Полис на основании ст. 435 Гражданского Кодекса РФ удостоверяет факт
                            заключения Договора страхования
                            между Страховщиком и Страхователем (далее - Стороны), предусмотренным настоящим Полисом, а
                            так же Правилами ДМС Страховщика.
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table" style="margin-bottom: 4px;">
                    <tbody>
                    <tr>
                        <td class="text-white td-dark-gray">Страховщик</td>
                        <td>АО "Альфастрахование"</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-dark-gray" style="width: 400px">Данные о страхователе</td>
                        <td class="text-center" style="width: 30px">X</td>
                        <td class="td-gray" style="text-wrap: none">Страхователь является Застрахованным</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-gray">ФИО полностью</td>
                        <td>{{ $contract->subject_fullname }}</td>
                        <td class="td-gray text-center">Дата рождения</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($contract->subject_value['birthDate'])->format("d.m.Y") }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-gray">Паспортные данные</td>
                        <td class="td-gray">Серия</td>
                        <td>{{ $contract->subject_value['docSeries'] }}</td>
                        <td class="td-gray">Номер</td>
                        <td>{{ $contract->subject_value['docNumber'] }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table" style="margin-bottom: 4px">
                    <tbody>
                    <tr>
                        <td class="td-gray">Телефон</td>
                        <td>{{ $contract->subject_value['phone'] }}</td>
                        <td class="td-gray">E-Mail</td>
                        <td>{{ $contract->subject_value['email'] }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-dark-gray">Данные о Застрахованном</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table" style="margin-bottom: 4px">
                    <tbody>
                    <tr>
                        <td class="td-gray">ФИО полностью</td>
                        <td>{{ $contract->objectsValue[0]['lastName'] }} {{ $contract->objectsValue[0]['firstName'] }} {{ $contract->objectsValue[0]['middleName'] ?? '' }}</td>
                        <td class="td-gray text-center">Дата рождения</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($contract->objectsValue[0]['birthDate'])->format("d.m.Y") }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-dark-gray">Программа ДМС</td>
                        <td>Предоставление медицинских услуг по программе "АльфаТелемед" Стандарт</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-gray">Страховая сумма</td>
                        <td>{{ $contract->insured_sum }}</td>
                        <td class="td-gray">Страховая премия</td>
                        <td>{{ $contract->premium }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-gray">Дата оформления Полиса</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($contract->signed_at)->format("d.m.Y") }}</td>
                        <td class="td-gray">Срок действия полиса</td>
                        <td class="td-gray text-center">с</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($contract->active_from)->format("d.m.Y") }}</td>
                        <td class="td-gray text-center">по</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($contract->active_to)->format("d.m.Y") }}</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td>Настоящий Полис вступает в силу по истечении 14 (четырнадцати) календарных дней с даты
                            оплаты страховой премии.
                            В случае неоплаты премии в указанный срок настоящий Полис считается не вступившим в силу.
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="td-gray">Оплата</td>
                        <td>Оплата Страхователем страховой премии единовременно в полном объеме</td>
                        <td class="td-gray">Дата оплаты премии</td>
                        <td class="text-center ">18.05.2021</td>
                    </tr>
                    </tbody>
                </table>

                <table class="inner-table">
                    <tbody>
                    <tr>
                        <td class="no-border" style="vertical-align: top">
                            <ul class="list">
                                <li>Акцептом настоящего Полиса Страхователь подтверждает достоверность своих
                                    персональных данных, изложенных в Полисе. Отсутствие всех или части персональных
                                    данных Страхователя в Полисе Стороны признают отказом Страхователя предоставлять
                                    соответствующие данные Страхов- щику с целью исполнения последним либо его
                                    представителем требо- ваний Федерального закона от 22 мая 2003 г. No 54-ФЗ «О
                                    применении контрольно-кассовой техники при осуществлении расчетов в Россий- ской
                                    Федерации», в том числе в части оформления и направления Стра- хователю документа,
                                    подтверждающего оплату страховой премии.
                                </li>
                                <li>Страховщик обязуется при обработке персональных данных Застрахо- ванных,
                                    предоставленных ему Страхователем, соблюдать требования Федерального закона от 27
                                    июля 2006 г. о «О персональных данных» No 152-ФЗ и других нормативных документов,
                                    обеспечивающих без- опасность персональных данных при их обработке.
                                </li>
                                <li>Страховщик вправе использовать факсимильную подпись, полученную с помощью средств
                                    механического и иного копирования, электронно- цифровую подпись либо иной аналог
                                    собственноручной подписи.
                                </li>
                                <li>В случае отказа Страхователя от Полиса в течение 14 (четырнадцати) ка- лендарных
                                    дней со дня его заключения последний направляет в адрес Страховщика письменное
                                    уведомление в течение указанного срока. При этом Страховщик возвращает Страхователю
                                    уплаченную страхо- вую премию в полном объеме в срок, не превышающий 10 (десяти)
                                    рабочих дней со дня получения письменного заявления Страхователя об отказе от
                                    Полиса, наличными денежными средствами или в безна- личном порядке.
                                </li>
                            </ul>
                        </td>
                        <td class="no-border" style="vertical-align: top">
                            <ul class="list">
                                <li>В случае отказа Страхователя от Полиса по истечении 14 (четырнадца- ти) календарных
                                    дней с даты его оформления Страхователь направляет Страховщику письменное
                                    уведомление не позднее, чем за 30 (тридцать) календарных дней до даты
                                    предполагаемого прекращения действия Полиса. При этом возврат страховой премии не
                                    производится.
                                </li>
                                <li>Оплата Страхователем страховой премии в полной объеме считается полным и
                                    безоговорочным акцептом на признание Полиса заклю- ченным на предложенных условиях,
                                    а также подтверждением того, что Страхователь согласен на обработку Страховщиком и
                                    уполномочен- ными им третьими лицами сведений, указанных в настоящем Полисе (а также
                                    иных персональных данных, получаемых Страховщиком при исполнении настоящего Полиса,
                                    в том числе биометрических и специ- альных), любыми способами, установленными
                                    законом, с целью испол- нения настоящего Полиса (Договора страхования).
                                </li>
                                <li>Акцептом настоящего Полиса Страхователь подтверждает ознаком- ление с Правилами
                                    страхования и Политикой Страховщика в отно- шении обработки персональных данных,
                                    размещенными на сайте https://www.alfastrah.ru/.
                                </li>
                            </ul>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<img class="two-page-image" src="data:image/png;base64, {{ base64_encode(file_get_contents(public_path('/alfa/images/back-2-full.jpg'))) }}">
</body>
</html>
