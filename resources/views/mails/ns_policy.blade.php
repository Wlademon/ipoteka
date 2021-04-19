<table style="background: #f8f8f8;width: 900px; margin: 0 auto;">
    <tr>
        <td class="container" style="padding:30px; font-family: sans-serif;">
            <img src="{{ $message->embed(resource_path('images/strahovka-logo.png')) }}" style="max-height: 110px;" height="110px">
            <br/>
            <p style="margin: 20px 0 0 0; font-size: 32px; font-weight: bold;">
                Спасибо за выбор Страховка.ру!
            </p>
            <p style="margin: 16px 0 0 0; font-size: 20px;">
                Уважаемый(ая) {{ $data->receiver }}, Вы приобрели страховой полис Телемедицина.<br/>
                Печатную форму Вы найдете во вложении.<br/><br/>
            </p>
            @if($data->insurRules)
                <p style="margin: 16px 0 0 0; font-size: 20px;">
                    Правила страхования по Телемедицине Вы можете скачать по <a href="{{ $data->insurRules }}" target="_blank">ссылке</a>.<br/><br/>
                </p>
            @endif


            <p style="margin-top: 20px; font-size: 16px; line-height: 1.75; color: #7f7f7f;">
                С уважением,<br/>
                Команда © Страховка.Ру
            </p>
            <p style="margin-top: 20px; font-size: 16px; line-height: 1.75; color: #000000;">
                <br/>+7 800 775-10-12
            </p>
            <p style="margin-top: 20px; font-size: 14px; line-height: 1.75; color: #7f7f7f;">
                Сотрудничаем с ведущими страховыми компаниями и помогаем быстро, просто и безопасно оформлять выгодные страховки.
            </p>
        </td>
    </tr>
</table>
