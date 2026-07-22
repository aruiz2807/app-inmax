<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Prestación de Servicios</title>

    <style>
        @page {
            margin: 30px 30px 60px 30px;
            size:legal portrait;
        }
          
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2f3e55;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
        }

        .header-left {
            width: 50%;
            vertical-align: middle;
        }

        .header-right {
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }

        .logo {
            height: 55px;
        }

        .logo-sub {
            color: #50D6D1;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 2.5px;
            margin-top: 2px;
        }

        .document-label {
            color: #9AA3B2;
            font-size: 10px;
            letter-spacing: 2px;
        }

        .document-title {
            color: #1D3D6E;
            font-size: 12px;
            font-weight: bold;
        }

        .divider {
            height: 2px;
            background: #50D6D1;
            margin: 10px 0;
        }

        .intro-box {
            background: #F7F7F7;
            border: 1px solid #D5DCE6;
            border-left: 6px solid #50D6D1;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
        }

        .intro-box p {
            font-size: 10px;
            text-align: justify;
            margin: 0;
        }

        .section {
            margin-top: 15px;
        }

        .section-title {
            text-align: center;
            color: #1D3D6E;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .section-line {
            width: 80px;
            height: 3px;
            background: #50D6D1;
            margin: 0 auto 5px auto;
        }

        .two-columns {
            width: 100%;
        }

        .column {
            width: 45%;
            vertical-align: top;
        }   

        .column p, .column ul, .column ol, .column li {
            text-align: justify;
            margin-bottom: 5px;
            font-size: 9px;
            padding-right: 20px;
        }

        .column ul {
            padding-left: 15px;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .column ol {
            padding-left: 15px;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .clause-title {
            color: #1D3D6E;
            font-weight: bold;
            font-size: 10px;
            border-left: 2px solid #50D6D1;
            padding-left: 10px;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .page-break {
            page-break-after: always;
        }

        strong {
            color: #1D3D6E;
        }

        /* ---------- Signature Boxes ---------- */
        .firma-intro {
            font-size: 10px;
            text-align: justify;
            margin: 40px 0 15px 0;
            color: #2f3e55;
            line-height: 1.6;
        }
        .firma-box {
            text-align: center;
            border: 1px solid #D5DCE6;
            border-top: 3px solid #1D3D6E;
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
            background: #F7F7F7;
        }
        .firma-box p {
            font-size: 10px;
            text-align: center;
            margin: 0;
            color: #2f3e55;
            line-height: 1.6;
        }
        .linea-firma {
            margin: 65px auto 10px auto;
            border-top: 1.5px solid #1D3D6E;
            width: 80%;
        }
        .firma-box p span {
            display: block;
            margin-top: 3px;
        }

        .page-footer {
            display: block;
            position: fixed;
            left: 0;
            right: 0;
            bottom: -35px;
            padding: 0;
            height: 30px;
            line-height: 30px;
            font-size: 8px;
            color: #9AA3B2;
            opacity: 0.8;
            border-top: 1px solid #D5DCE6;
            z-index: 1000;
        }
        
        .page-footer .right {
            float: right;
        }
    </style>
</head>

<body>
    {{-- FOOTER --}}
    <div class="page-footer">INMAX · Contrato de prestación de servicios <span class="right">APP INMAX</strong></div>

    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img class="logo" src="{{ public_path('/img/LogoINMAXSUP.png') }}" alt="Logo">
                    <div class="logo-sub">PLATAFORMA DIGITAL</div>
                </td>

                <td class="header-right">
                    <div class="document-label">DOCUMENTO LEGAL</div>
                    <div class="document-title">Contrato de prestación de servicios</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

{{-- PAGE 1 --}}

    {{-- INTRO --}}
    <div class="intro-box">
        <p>
            CONTRATO DE PRESTACIÓN DE SERVICIOS PARA USUARIO DE LA PLATAFORMA DIGITAL APP INMAX, que celebran por una parte
            <strong>ÁNGEL ISRAEL NUÑO BONALES</strong>, a quien en lo sucesivo se le denominará <strong>“INMAX”</strong>,
            y por la otra <strong>{{ Str::upper($info->legal_name) }}</strong>, a quien en lo sucesivo se le denominará <strong>“USUARIO”</strong>,
            ambos en su conjunto se denominarán como las “PARTES” al tenor de las siguientes DECLARACIONES y CLÁUSULAS:
        </p>
    </div>

    {{-- SECTION 1: DECLARACIONES --}}
    <div class="section">
        <div class="section-title">I. DECLARACIONES</div>
        <div class="section-line"></div>
        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="clause-title">I.1 Declara INMAX que:</div>
                    <p><strong>a)</strong> Es una persona física, con pleno uso y goce de sus facultades, con personalidad jurídica propia y con capacidad jurídica suficiente para celebrar el presente contrato.</p>
                    <p><strong>b)</strong> Es propietario de la plataforma tecnológica denominada <strong>INMAX y su sitio web</strong> (en adelante <strong>“APP INMAX”</strong>), <strong>destinada al uso del Usuario para la coordinación, administración y facilitación de acceso a servicios de salud y atención médica privados independientes</strong> a través de una red de Prestadores de Servicios de la Salud independientes.</p>
                    <p><strong>c)</strong> <strong>No es un establecimiento para la atención médica,</strong> ni cualquier otro servicio de salud, ni presta servicios de atención médica al Usuario ni cualquier otra persona, <strong>no es institución de seguros médicos o relacionados con la Salud,</strong> no realiza actividades reservadas conforme a la Ley de Instituciones de Seguros y de Fianzas y no otorga cobertura financiera, indemnización, reembolso ni protección patrimonial por gastos médicos catastróficos.</p>
                    <p><strong>d)</strong> Los médicos, laboratorios, hospitales y farmacias que integran la red de la <strong>APP INMAX no son empleados, mandatarios ni representantes de ningún tipo</strong> de INMAX.</p>
                    <p><strong>e)</strong> Tiene su domicilio para notificaciones derivadas del presente instrumento en: <strong>Av. Plan de San Luis #1817, Col. San Bernardo, C.P. 44260 en Guadalajara, Jalisco, México</strong> y con Registro Federal de Contribuyentes número: <strong>NUBA820803H68</strong>.</p>
                </td>

                <td class="column">
                    <div class="clause-title">I.2 Declara el USUARIO que:</div>
                    <p><strong>a)</strong> Es mayor de edad y tiene capacidad legal para contratar.</p>
                    <p><strong>b)</strong> Solicita voluntariamente la contratación a los servicios del sistema de la plataforma <strong>APP INMAX</strong>.</p>
                    <p><strong>c)</strong> Reconoce que recibió información clara, suficiente y comprensible sobre los alcances, beneficios, limitaciones, exclusiones y naturaleza del servicio.</p>
                    <p><strong>d)</strong> Entiende y acepta expresamente que los servicios de la <strong>APP INMAX NO SON DE ATENCIÓN MÉDICA NI ES UN SEGURO DE GASTOS MÉDICOS</strong>.</p>
                    <p><strong>e)</strong> Ha leído y acepta por completo los Términos y Condiciones de Uso, el Aviso Legal, las Políticas de Pagos y Cancelaciones y el Aviso de Privacidad Integral publicado en la <strong>APP INMAX</strong>.</p>
                    <p><strong>f)</strong> Manifiesta que los datos personales y médicos proporcionados son veraces, completos y actualizados, y brindados de forma voluntaria, liberando a <strong>INMAX</strong> de cualquier responsabilidad derivada de información falsa u omitida.</p>
                    <p><strong>g)</strong> Tiene su domicilio para notificaciones derivadas del presente instrumento en: <strong>{{ $info->legal_address }}</strong> y cuenta con Registro Federal de Contribuyentes número: <strong>{{ Str::upper($info->cfdi_rfc) }}</strong>.</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- SECTION 2: CLAUSULAS --}}
    <div class="section">
        <div class="section-title">II. CLÁUSULAS</div>
        <div class="section-line"></div>

        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="clause-title">PRIMERA. OBJETO</div>
                    <p><strong>INMAX</strong> se obliga a poner a disposición del <strong>USUARIO</strong> el acceso a la plataforma <strong>APP INMAX,</strong> para que pueda hacer uso de sus funcionalidades de <strong>coordinación, administración, comunicación con los Prestadores de Servicios de la Salud y facilitación de acceso</strong> a servicios de atención médica privada independiente, exclusivamente conforme al Plan contratado y a los beneficios descritos en el presente Contrato, y de acuerdo con los Términos y Condiciones de Uso publicados en la plataforma <strong>APP INMAX</strong>, a través de Prestadores de Servicios de la Salud independientes que integran la red de <strong>INMAX</strong>.</p>
                    <p>Señalando para el presente Contrato como Número de Miembro: <strong>{{$policy->number}}</strong></p>
                    <p>En caso de que la presente contratación sea destinada para un tercero, un menor de edad o personas que, por disposición legal, requieran representación para la celebración de actos jurídicos o para la gestión de los servicios derivados de la plataforma <strong>APP INMAX</strong>, se realizarárealizara una incorporación como Beneficiario señalando los datos específicos en el <strong>ANEXO II. INCORPORACION DE BENEFICIARIOS Y AUTORIZACIÓN.</strong></p>
                    <p>La incorporación de Beneficiarios no crea una relación contractual independiente con <strong>INMAX</strong>, permaneciendo el firmante como Titular y único responsable frente a la presente relación contractual.</p>
                </td>

                <td class="column">
                    <div class="clause-title">SEGUNDA. NATURALEZA JURÍDICA DEL SERVICIO</div>
                    <p>Las <strong>PARTES</strong> reconocen que:</p>
                    <p><strong>a)</strong> La plataforma <strong>APP INMAX</strong> únicamente realiza servicios para la coordinación, administración y facilitación de acceso tecnológico, no es un servicio de seguro médico o relacionado con la salud, ni es para brindar directamente servicios de salud y de atención médica a través de la plataforma <strong>APP INMAX</strong>.</p>
                    <p><strong>b)</strong> La plataforma <strong>APP INMAX</strong> no cuenta con obligación de dar un resultado médico, no genera diagnósticos, interpretaciones de estudios médicos o de salud, no vende medicamentos, consultas médicas, ni receta alguna, sino que únicamente tiene la obligación de proveer medios digitales para el cumplimiento de su objeto.</p>
                    <p><strong>c)</strong> La relación médico-paciente se establece únicamente entre el <strong>USUARIO</strong> y los Prestadores de Servicios de la Salud, por lo que <strong>INMAX</strong> se deslinda de cualquier responsabilidad legal y médica que exista en consecuencia de los servicios de salud y de atención médica entre el <strong>USUARIO</strong> y los Prestadores de Servicios de la Salud.</p>
                </td>
            </tr>
        </table>

{{-- PAGE 2 --}}

        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="clause-title">TERCERA. VIGENCIA</div>
                    <p>El presente Contrato contará con una vigencia de 12 (doce) meses, las condiciones de operación se informarán a través de la <strong>APP INMAX</strong>, y sus Términos y Condiciones de Uso publicados.</p>
                    <p>El plazo de la vigencia comenzará a contar una vez se haya firmado el presente Contrato y <strong>al quinto día natural posterior de la confirmación del pago total del Plan contratado</strong>, sin posibilidad de suspensión, congelamiento, traspaso o prórroga automática.</p>
                    <p>En caso de no contar con un nuevo Contrato vigente <strong>INMAX</strong> podrá dar de baja el acceso, usuario y credenciales, restringiendo el acceso completo al <strong>USUARIO</strong> a la plataforma <strong>APP INMAX</strong>.</p>

                    <div class="clause-title">CUARTA. PAGOS Y CANCELACIONES</div>
                    <p>El USUARIO acepta que los accesos a ciertos Beneficios y Cupones requieren de un pago de un Plan contratado para el aprovechamiento de los servicios de la plataforma APP INMAX, además el USUARIO acepta que una vez que sus Beneficios y/o Cupones se agoten o el servicio de atención médica que solicite no forma parte de estos, tendrán un costo adicional y se deberá de pagar al solicitarlo. Todos los costos, Beneficios, Cupones y plazos se informarán a través de la APP INMAX, así como los medios autorizados para que el USUARIO realice el pago de los mismos.</p>                    
                    <p>El costo and el Plan contratado para el objeto del presente Contrato, será aquel el USUARIO señale en la plataforma y realice el pago, por lo que el mismo será vinculante y formará parte del presente Contrato.</p>
                    <p>Los pagos y cancelaciones estarán sujetas a la Política de Pagos y Cancelaciones publicada en la plataforma APP INMAX.</p>

                    <div class="clause-title">QUINTA. BENEFICIOS Y CUPONES INCLUIDOS</div>
                    <p>Cuando el <strong>USUARIO</strong> contrate su Plan de la plataforma <strong>APP INMAX</strong> tendrá acceso a ciertos Beneficios and Cupones, las descripciones de los Beneficios y Cupones incluidos del Plan contratado estarán publicadas en la plataforma <strong>APP INMAX.</strong> La cantidad de Beneficios y Cupones a disfrutar por parte del <strong>USUARIO</strong> será el señalado de acuerdo con el Plan contratado y acatado en la plataforma <strong>APP INMAX.</strong></p>
                    <p>Una vez que se agoten los Beneficios y Cupones incluidos en la contratación, el <strong>USUARIO</strong> podrá seguir solicitando los servicios de atención médica con los Prestadores de Servicios de la Salud de manera individual a precios preferenciales de acuerdo con los precios y modalidades de pago publicados en la plataforma <strong>APP INMAX.</strong></p>
                    <p>Los Beneficios y Cupones incluidos en el Plan contratado, no podrán ser renovados hasta concluir la vigencia del presente Contrato.</p>
                    <p>Todos los servicios, Beneficios y Cupones:</p>
                    <ul>
                        <li>Son <strong>personales, intransferibles y no acumulables.</strong></li>
                        <li>No generan reembolsos en efectivo.</li>
                        <li>No aplican retroactivamente.</li>
                        <li>Estarán limitados a la disponibilidad de los Prestadores de Servicios de la Salud y de la plataforma <strong>APP INMAX</strong>.</li>
                    </ul>

                    <div class="clause-title">SEXTA. EXCLUSIONES GENERALES</div>
                    <p>Dentro de los Servicios de salud y atención médica que pueden brindar los Prestadores de Servicios de la Salud, quedan expresamente excluidos, sin excepción:</p>
                    <p><strong>a)</strong> Urgencias médicas y atención prehospitalaria.<br>
                    <strong>b)</strong> Hospitalización mayor o prolongada.<br>
                    <strong>c)</strong> Cirugías mayores.<br>
                    <strong>d)</strong> Terapias intensivas.<br>
                    <strong>e)</strong> Tratamientos oncológicos, trasplantes, diálisis.<br>
                    <strong>f)</strong> Medicamentos y estudios fuera de catálogo.<br>
                    <strong>g)</strong> Complicaciones derivadas de enfermedades preexistentes no declaradas.</p>
                </td>

                <td class="column">
                    <div class="clause-title">SÉPTIMA. CITAS, HORARIOS Y DISPONIBILIDAD DE LOS SERVICIOS DE SALUD Y ATENCIÓN MÉDICA</div>
                    <p>Las citas para agendar los servicios de salud y de atención médica se otorgarán previa solicitud con <strong>mínimo 12 (doce) horas de anticipación</strong>, sujetas a disponibilidad real de la agenda del Prestador de Servicios de la Salud. <strong>INMAX</strong> no garantiza horario específico ni Prestador de Servicios de la Salud determinado, estos deberán de ser elegidos por el <strong>USUARIO</strong> dentro de la <strong>APP INMAX</strong> de acuerdo con la disponibilidad y horarios de los Prestadores de Servicios de la Salud.</p>
                    <p>Una vez generada la cita, no podrá ser cancelada ni modificada por el <strong>USUARIO</strong> con al menos 12 (doce) horas de anticipación al horario en que fue agendada. En caso de no cancelar la cita dentro de las 12 (doce) horas previas al horario en que fue agendado o no presentarse en el horario y lugar acordado en la cita, <strong>INMAX</strong> y el Prestador de Servicios de la Salud podrán considerar la misma como realizada, pudiendo reducir la cita y el servicio de atención médica de los Beneficios y/o Cupones incluidos en el Plan contratado o generar el cobro completo del servicio de atención médica, obligándose el <strong>USUARIO</strong> a realizar el pago.</p>
                    <p>El <strong>USUARIO</strong> tendrá la responsabilidad de acudir a la cita en las condiciones que se especifican en la plataforma y/o de acuerdo a las recomendaciones e indicaciones que el Prestador de Servicios de la Salud señale para el servicio de atención médica y cita agendada, en caso de no acudir en las condiciones e indicaciones necesarias para poder proceder con la cita o en caso que esto genere como consecuencia, no ser posible realizar el estudio, la atención médica o tratamiento clínico, <strong>INMAX</strong> y el Prestador de Servicios de la Salud no serán responsables, pudiendo señalar la cita como realizada descontando la misma de los Beneficios y/o Cupones incluidos en su Plan contratado o será responsable de cubrir el costo del servicio de atención médica y cita agendada, obligándose el <strong>USUARIO</strong> a realizar el pago.</p>
                    
                    <div class="clause-title">OCTAVA. OBLIGACIONES DEL USUARIO</div>
                    <p>El USUARIO se obliga a:</p>
                    <ol>
                        <li>Proporcionar dentro de la APP INMAX y a los Prestadores de Servicios de la Salud información veraz.</li>
                        <li>No usar la APP INMAX de forma ilegal, o darle un mal uso o cualquier uso que no sea permitido por INMAX.</li>
                        <li>No suplantar la identidad de nadie o fingir una identidad inventada.</li>
                        <li>No revender los servicios de la APP INMAX, los Beneficios y Cupones de su Plan contratado, los servicios de atención médica ni revender o generar cualquier tipo de lucro con cualquiera de los servicios ofrecidos por los Prestadores de Servicios de la Salud.</li>
                        <li>Tratar a los Prestadores de Servicios de la Salud de la red de la APP INMAX con respeto y no realizar acciones ilegales y/o que puedan perjudicar el bien físico, material, psicológico y profesional de los Prestadores de Servicios de la Salud tratantes.</li>
                        <li>Operar y usar la cuenta de acceso que INMAX le otorgue para el objeto del presente Contrato de manera lícita, responsable y bajo todos los lineamientos de seguridad para la protección de los datos necesaria.</li>
                        <li>Reportar directamente en la APP INMAX cualquier anomalía que se detecte con los Prestadores de Servicios de la Salud tratantes o sus servicios de atención médica.</li>
                    </ol>

                    <div class="clause-title">NOVENA. RESPONSABILIDAD MÉDICA</div>
                    <p>El <strong>USUARIO</strong> reconoce y acepta que:</p>
                    <p><strong>a)</strong> <strong>INMAX y su plataforma APP INMAX NO prestan directamente servicios de atención médica y/o de salud, ni servicios de seguros médicos o para la salud</strong>.<br>
                    <strong>b)</strong> Todo diagnóstico, notas médicas, tratamiento, pronóstico, receta, prescripción y procedimiento de atención médica o de salud, es <strong>responsabilidad exclusiva del Prestador de Servicios de la Salud independiente</strong>.<br>
                    <strong>c)</strong> El acto médico conlleva riesgos inherentes, aun cuando se realice conforme a la lex artis médica.</p>
                </td>
            </tr>
        </table>
    
{{-- PAGE 3 --}}

        <table class="two-columns">
            <tr>
                <td class="column">
                    <p><strong>d)</strong> <strong>INMAX no será responsable</strong> por complicaciones, secuelas, resultados adversos, omisiones, errores de diagnóstico o evolución clínica, ni ninguna consecuencia que deriven de los servicios de salud y de atención médica, realizados por los Prestadores de Servicios de la Salud al <strong>USUARIO</strong>.<br>
                    <strong>e)</strong> <strong>APP INMAX</strong> no es, ni deberá interpretarse como, una institución médica, hospital, clinic, consultorio, farmacia o establecimiento de atención médica, ni presta servicios de atención médica o sanitarios de ninguna naturaleza ni como una Institución de Seguros médicos o relacionados con la salud o Fianzas.</p>

                    <div class="clause-title">DÉCIMA. CONFIDENCIALIDAD Y DATOS PERSONALES</div>
                    <p>El tratamiento de datos personales se realizará conforme a la <strong>Ley Federal de Protección de Datos Personales en Poresión de los Particulares</strong>, su Reglamento y Aviso de Privacidad Integral publicado en la plataforma <strong>APP INMAX</strong>.</p>

                    <div class="clause-title">DÉCIMA PRIMERA. MEDIOS ELECTRÓNICOS Y CUENTA DE ACCESO</div>
                    <p>El <strong>USUARIO</strong> acepta que:</p>
                    <p><strong>a)</strong> La <strong>APP INMAX</strong> es únicamente un medio oficial de gestión para coordinar, administrar y facilitar el acceso a servicios de salud y de atención médica privados independientes a través de una red de Prestadores de Servicios de la Salud Independientes.<br>
                    <strong>b)</strong> Para el uso de los Servicios de la plataforma <strong>APP INMAX</strong> y sus funcionalidades, <strong>INMAX</strong> le brindará acceso a una cuenta y su contraseña dentro de la plataforma <strong>APP INMAX.</strong><br>
                    <strong>c)</strong> Es responsable total del uso la cuenta que <strong>INMAX</strong> le brinde acceso y su usuario y contraseña, así como su operation, confidencialidad y protección de los datos que se pudieran visualizar y obtener.<br>
                    <strong>d)</strong> Los registros electrónicos tienen plena validez legal por lo que el usuario generado en la <strong>APP INMAX</strong> y sus datos de contacto, costo del Plan contratado, Beneficios y Cupones, y demás aplicables y generados dentro de la <strong>APP INMAX</strong> serán vinculatorios al presente Contrato.<br>
                    <strong>e)</strong> Toda la información que registre en la plataforma <strong>APP INMAX</strong> es veraz y cualquier información falsa será responsabilidad del <strong>USUARIO.</strong></p>

                    <div class="clause-title">DÉCIMA SEGUNDA. LIMITACIÓN DE RESPONSABILIDAD</div>
                    <p>La responsabilidad máxima de <strong>INMAX</strong>, únicamente será relacionada con el objeto de la <strong>APP INMAX</strong>, la cual está relacionada a sus servicios de <strong>coordinación, administración y facilitación de acceso</strong> a servicios de atención médica privada a través de Prestadores de Servicios de la Salud independientes, en cualquier caso, <strong>no excederá el monto efectivamente pagado por el USUARIO</strong> por el Plan contratado, excluyendo daños indirectos, morales, lucro cesante o pérdida de oportunidad.</p>

                    <div class="clause-title">DÉCIMA TERCERA. CASO FORTUITO Y FUERZA MAYOR</div>
                    <p><strong>INMAX</strong> no será responsable por la imposibilidad de prestar servicios derivados de causas ajenas a su control, incluyendo pandemias, desastres naturales, fallas tecnológicas, huelgas, cualquier caso fortuito o de fuerza mayor de los Prestadores de Servicios de la Salud o disposiciones gubernamentales.</p>

                    <div class="clause-title">DÉCIMA CUARTA. TERMINACIÓN ANTICIPADA</div>
                    <p>El presente Contrato podrá darse por terminado sin responsabilidad para <strong>INMAX</strong> en caso de que el <strong>USUARIO</strong> realice:</p>
                    <ol>
                        <li>Uso indebido del sistema y/o de la plataforma <strong>APP INMAX.</strong></li>
                        <li>Fraude o simulación.</li>
                        <li>Que el <strong>USUARIO</strong> realice alguna de las Prohibiciones de la cláusula Décima Quinta.</li>
                        <li>Proporcione información falsa.</li>
                        <li>Cualquier tipo de delito o violación en contra de la Ley.</li>
                        <li>Comportamientos que no lleguen a ser compatibles o relacionados con la identidad de la plataforma <strong>APP INMAX</strong> y con las ideologías y valores de <strong>INMAX</strong>.</li>
                        <li>Cualquier tipo de incumplimiento al presente Contrato y a sus obligaciones.</li>
                    </ol>
                </td>

                <td class="column">
                    <div class="clause-title">DÉCIMA QUINTA. PROHIBICIONES</div>
                    <p>Una vez contratados los servicios de INMAX, el USUARIO tiene prohibido:</p>
                    <ol>
                        <li>Cambiar la modalidad de su contratación de individual a colectiva o viceversa.</li>
                        <li>Revender los servicios de la APP INMAX, los servicios de atención médica, beneficios, Cupones y/o generar algún tipo de lucro por sus medios.</li>
                        <li>Realizar alguna actividad ilícita en la plataforma INMAX o a través de los Prestadores de Servicios de la Salud.</li>
                        <li>Compartir los datos de su usuario y contraseña o dejar ingresar alguna persona diferente a él, para hacer uso de los servicios de la APP INMAX, solicitar servicios de salud y atención médica o acceder a la información.</li>
                        <li>Transferir los derechos, Beneficios, Cupones o servicios contratados en la plataforma APP INMAX.</li>
                    </ol>

                    <div class="clause-title">DÉCIMA SEXTA. PROPIEDAD INTELECTUAL</div>
                    <p>Todos los derechos de propiedad intelectual relacionados con la Plataforma <strong>APP INMAX</strong>, incluyendo software, diseño, logotipos, sitio web, textos y funcionalidades, son propiedad de <strong>INMAX</strong>, por lo que el uso y contratación de los servicios de <strong>APP INMAX</strong> en ningún momento se deberá de interpretar como algún tipo de cesión, transmisión, venta o adquisición de los derechos de cualquier propiedad intelectual de <strong>INMAX</strong> y su plataforma <strong>APP INMAX</strong>.</p>

                    <div class="clause-title">DÉCIMA SÉPTIMA. SOMETIMIENTO A MEDIOS DE JUSTICIA ALTERNA</div>
                    <p>Las <strong>PARTES</strong> acuerdan que toda discrepancia, conflicto, cuestión o reclamación resultantes de la ejecución o interpretación del presente Contrato será resuelta primeramente mediante solución de conflictos por medio de justicia Alternativa a través del mecanismo de mediación, aceptando el procedimiento establecido en la LEY DE JUSTICIA ALTERNATIVA DEL ESTADO DE JALISCO, que las <strong>PARTES</strong> declaran expresamente conocer y aceptar y por el que se dirimirá cualquier posible conflicto concertado entre ellas.</p>
                    <p>Para efectos de la presente cláusula, la <strong>PARTE</strong> que considere afectado cualquiera de sus derechos derivados de la interpretación, ejecución, cumplimiento o terminación del presente Contrato deberá notificarlo por escrito a la otra <strong>PARTE</strong>, expresando de manera clara los hechos que motivan la controversia. A partir de dicha notificación, las partes contarán con un plazo de 30 (treinta) días naturales para participar de buena fe en el procedimiento de justicia alternativa a través de su mecanismo de mediación y, en su caso, celebrar un convenio por escrito que resuelva la controversia. Si transcurrido dicho plazo no se alcanzare un acuerdo o convenio debidamente firmado por ambas <strong>PARTES</strong>, cualquiera de ellas podrá ejercitar las acciones legales que estime procedentes, sometiéndose expresamente a la jurisdicción y competencia de los tribunales y juzgados competentes de Guadalajara, Jalisco, renunciando a cualquier otro fuero que pudiera corresponderles en razón de su domicilio presente o futuro o por cualquier otra causa.</p>

                    <div class="clause-title">DÉCIMA OCTAVA. LEGISLACIÓN Y JURISDICCIÓN</div>
                    <p>Para todo lo relacionado o no previsto en el presente Contrato y su Anexo, las partes se someten a las leyes de los <strong>Estados Unidos Mexicanos</strong> y a los tribunales competentes en la ciudad de <strong>Guadalajara, Jalisco</strong>, renunciando por tanto a cualquiera otra jurisdicción a la cual pudiere tener derecho en virtud de su domicilio presente o futuro.</p>
                </td>
            </tr>
        </table>
    </div> <br><br><br>

{{-- PAGE 4 --}}

    {{-- SECTION 3: ANEXO MEDICO LEGAL --}}
    <div class="section">
        <div class="section-title"><br>III. ANEXO I. MÉDICO-LEGAL</div>
        <div class="section-line"></div>

        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="clause-title">I. CONSENTIMIENTO INFORMADO</div>
                    <p>Previo a cualquier procedimiento, el <strong>USUARIO</strong> firmará el consentimiento informado correspondiente, liberando a <strong>INMAX</strong> de cualquier reclamación relacionada con el acto médico. El Prestador de Servicios de la Salud, será el responsable de recabar la firma del <strong>USUARIO</strong> y todo lo relacionado con los servicios de salud y de atención médica, liberando a <strong>INMAX</strong> de cualquier reclamo, demanda, denuncia, mala práctica o consecuencia que derive de estos servicios de salud y de atención médica siendo en todo momento el responsable el Prestador de Servicios de la Salud tratante.</p>
                    <p>El <strong>USUARIO</strong> declara que:</p>
                    <p><strong>a)</strong> Acepta que el consentimiento informado será recabado de manera independiente por los Prestadores de Servicios de la Salud tratantes por cada procedimiento, consulta, estudio o tratamiento, ya sea en formato físico o electrónico.</p>
                    <p><strong>b)</strong> Libera expresamente a <strong>INMAX</strong> de cualquier responsabilidad derivada del acto médico y de los servicios de salud y de atención médica, siendo esta exclusiva del Prestador de Servicios de la Salud.</p>

                    <div class="clause-title">II. INTERCONSULTA MÉDICA</div>
                    <p><strong>a)</strong> Las consultas de especialidad sólo podrán otorgarse mediante <strong>formato de interconsulta</strong> emitido por un médico de la red de Prestadores de Servicios de la Salud de <strong>INMAX</strong>.<br>
                    <strong>b)</strong> La interconsulta <strong>no garantiza diagnóstico, tratamiento específico ni tiempo de atención determinado</strong>.<br>
                    <strong>c)</strong> La atención por el especialista queda sujeta a disponibilidad de agenda y criterios médicos del Prestador de Servicios de la Salud tratante.<br>
                    <strong>d)</strong> <strong>INMAX no interviene ni influye</strong> en las decisiones clínicas del especialista, ni en los servicios de atención médica ni nada relacionado con los servicios de salud.</p>
                </td>

                <td class="column">
                    <div class="clause-title">III. RECETAS MÉDICAS</div>
                    <p><strong>a)</strong> <strong>INMAX</strong> no emite recetas ni realiza servicios de atención médica o de salud dentro de la <strong>APP INMAX</strong>.<br>
                    <strong>b)</strong> Las recetas médicas serán emitidas <strong>exclusivamente por médicos con cédula profesional vigente a cargo de los Prestadores de Servicios de la Salud de manera independiente</strong>.<br>
                    <strong>c)</strong> Las recetas tendrán vigencia conforme a la normatividad sanitaria aplicable.<br>
                    <strong>d)</strong> La cobertura para el contacto con los Prestadores de Servicios de la Salud encargados de suministrar los medicamentos prescritos por los Prestadores de Servicios de la Salud encargado de proporcionar consultas médicas se limita al <strong>catálogo vigente que los Prestadores de Servicios de la Salud tengan</strong> y a los montos establecidos en la plataforma <strong>APP INMAX</strong>.<br>
                    <strong>e)</strong> El <strong>USUARIO</strong> reconoce que la prescripción médica <strong>no garantiza resultados clínicos específicos</strong>.</p>

                    <div class="clause-title">IV. NOTAS MÉDICAS</div>
                    <p>La plataforma APP INMAX ni INMAX genera o resguarda notas médicas, las anotaciones digitales disponibles en la plataforma APP INMAX constituyen únicamente resúmenes de los servicios, síntomas, hallazgos físicos, diagnósticos, tratamientos, notas y recomendaciones como un repositorio tecnológico de almacenamiento de información.</p> 
                    <p>INMAX no genera, emite, modifica, interpreta, valida ni asume responsabilidad alguna sobre el contenido médico o clínico incorporado en la plataforma APP INMAX. Estas notas o anotaciones dentro de la APP INMAX, no deberán de sustituir las notas médicas de los Prestadores del Servicios de la Salud, ni los Prestadores de Servicios de la Salud deberá de usarlas como sustitución de las notas médicas oficiales y reguladas por la ley aplicable.</p>
                    <p>Toda la información médica contenida en la Plataforma <strong>APP INMAX</strong> es generada e incorporada exclusivamente por los Prestadores de Servicios de la salud independientes, y el <strong>USUARIO</strong> quienes actúan bajo su propia responsabilidad profesional y legal, por lo que también serán responsables de cumplir con todas las normas y leyes correspondientes al respecto, siendo los <strong>Prestadores de Servicios de la Salud</strong> y los <strong>USUARIOS</strong> los que incorporen la información y los únicos responsables y obligados a su cumplimiento.</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- SECTION 4: ANEXO BENEFICIARIO --}}
    <div class="section">
        <div class="section-title"><br>IV. ANEXO II. INCORPORACIÓN DE BENEFICIARIO Y AUTORIZACIÓN</div>
        <div class="section-line"></div>

        <table class="two-columns">
            <tr>
                <td class="column">
                    <p>El presente <strong>ANEXO</strong> tiene por objeto regular la incorporación y gestión de beneficiarios, terceros, menores de edad o personas que, por disposición legal, requieran representación para acceder y utilizar las funcionalidades habilitadas dentro de la plataforma <strong>APP INMAX.</strong></p>

                    <div class="clause-title">PRIMERA. INCORPORACIÓN DEL BENEFICIARIO.</div>
                    <p><strong>{{ Str::upper($info->legal_name) }}</strong> (en adelante para este <strong>ANEXO</strong> el “<strong>USUARIO TITULAR</strong>”)  manifiesta su voluntad de incorporar como beneficiario a la persona cuyos datos de identificación se señalan en el presente <strong>ANEXO</strong>, para que pueda acceder y utilizar las funcionalidades y servicios tecnológicos disponibles dentro de la plataforma <strong>APP INMAX</strong>, en los términos permitidos por ésta.</p>
                    <p><u>DATOS DEL BENEFICIARIO</u></p>
                    <p>Nombre completo: <strong>{{ $info->same_as_user ? 'No aplica' : $policy->user->name }}</strong></p>
                    <p>Fecha de nacimiento: <strong>{{ $info->same_as_user ? 'No aplica' : \Carbon\Carbon::parse($policy->user->birth_date)->format('d/m/Y') }}</strong></p>
                    <p>Edad actual: <strong>{{ $info->same_as_user ? 'No aplica' : \Carbon\Carbon::parse($policy->user->birth_date)->age }}</strong></p>
                    <p>Relación con el <strong>USUARIO TITULAR</strong>: <strong>{{ $info->same_as_user ? 'No aplica' : $info->relationship->name}}</strong></p>
                </td>

                <td class="column">
                    <div class="clause-title">SEGUNDA. REPRESENTACIÓN Y FACULTADES.</div>
                    <p>El <strong>USUARIO TITULAR</strong> declara, bajo protesta de decir verdad, que cuenta con las facultades legales suficientes para actuar en representación del beneficiario y autorizar su incorporación a la plataforma <strong>APP INMAX</strong>, ya sea en calidad de padre, madre, tutor, representante legal o cualquier otra figura legalmente reconocida.</p>
                    <p>Asimismo, manifiesta que la información proporcionada es veraz, completa y actualizada, obligándose a mantenerla vigente durante toda la relación contractual.</p>

                    <div class="clause-title">TERCERA. AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES.</div>
                    <p>El <strong>USUARIO TITULAR</strong> autoriza expresamente a <strong>INMAX</strong> y su plataforma <strong>APP INMAX</strong> el tratamiento de los datos personales y los datos personales sensibles del beneficiario que resulten necesarios para la correcta operación, funcionamiento, administración y prestación de los servicios tecnológicos ofrecidos por la plataforma <strong>APP INMAX</strong>.</p>
                    <p>El <strong>USUARIO TITULAR</strong> manifiesta haber leído, comprendido y aceptado el Aviso de Privacidad Integral vigente publicado en la plataforma <strong>APP INMAX</strong>, otorgando su consentimiento en representación del beneficiario para el tratamiento de la información necesaria para la ejecución de las funcionalidades contratadas conforme a las finalidades establecidas en el Aviso de Privacidad Integral y demás políticas aplicables publicadas en el sitio web de la plataforma <strong>APP INMAX</strong>.</p>
                </td>
            </tr>
        </table>

        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="clause-title">CUARTA. SUPERVISIÓN Y RESPONSABILIDAD.</div>
                    <p>El <strong>USUARIO TITULAR</strong> será responsable del uso que se realice de la plataforma <strong>APP INMAX</strong>, así como de toda actividad, información, actualización, autorización, gestión, interacción y operación que se genere mediante todas las cuentas otorgadas por <strong>INMAX</strong> para el objeto de la presente contratación.</p>
                    <p>Asimismo, será responsable de supervisar el uso adecuado de la plataforma <strong>APP INMAX</strong> y de proporcionar información veraz y actualizada relacionada con el beneficiario.</p>                    

                    <div class="clause-title">QUINTA. ALCANCE DE LA RESPONSABILIDAD DE LA PLATAFORMA.</div>
                    <p>La incorporación de un beneficiario no modifica la naturaleza jurídica de los servicios prestados por la plataforma <strong>APP INMAX</strong>, los cuales se limitan exclusivamente <strong>al uso para la coordinación, administración y facilitación de acceso a servicios de salud y atención médica privados independientes</strong> a través de una red de Prestadores de Servicios de la Salud independientes.</p>                    
                    <p><strong>INMAX</strong> y la plataforma <strong>APP INMAX</strong> no adquieren la calidad de tutor, custodio, representante legal, institución de salud, prestador de servicios médicos, ni asume obligaciones de supervisión personal, médica o asistencial respecto del beneficiario.</p>
                </td>

                <td class="column">
                    <div class="clause-title">SEXTA. ACTUALIZACIÓN Y REVOCACIÓN.</div>
                    <p>El <strong>USUARIO TITULAR</strong> se obliga a informar oportunamente cualquier modificación relacionada con la representación legal, patria potestad, tutela, capacidad jurídica o cualquier circunstancia que afecte la permanencia del beneficiario dentro de la plataforma <strong>APP INMAX</strong>.</p>
                    <p><strong>INMAX</strong> podrá solicitar documentación adicional cuando resulte necesario para verificar la representación legal o garantizar el cumplimiento de las obligaciones legales aplicables.</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- FIRMAS --}}
    <div class="section">
        <p class="firma-intro">
        Firmado de conformidad el Contrato, su Anexo I y Anexo II por las <strong>PARTES,</strong> 
        el día <strong>{{ $info->created_at->translatedFormat('d') }}</strong> 
        del mes <strong>{{ $info->created_at->translatedFormat('F') }}</strong> 
        del año <strong>{{ $info->created_at->translatedFormat('Y') }}</strong>, 
        en la Ciudad de Guadalajara, Jalisco, México.
        </p>

        <table class="two-columns">
            <tr>
                <td class="column">
                    <div class="firma-box">
                        <p><strong>“INMAX”</strong></p>
                        <div class="linea-firma"></div>
                        <p>Firma<br><span class="destacado">ÁNGEL ISRAEL NUÑO BONALES</span></p>
                    </div>
                </td>

                <td class="column">
                    <div class="firma-box">
                        <p><strong>“USUARIO”</strong></p>
                        <div class="linea-firma"></div>
                        <p>Firma<br><span class="destacado">{{ Str::upper($info->legal_name) }}</span></p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>