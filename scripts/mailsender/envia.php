<?php

	/* nslookupmx
	   $domain - String - Dominio a ser retornado o MX
	   Retorna o MX do dominio que sera enviado a mensagem. */
	function nslookupmx($domain) {
		// Get the records
		getmxrr($domain, $mx_records, $mx_weight);
		// Put the records together in a array we can sort
		for($i=0;$i<count($mx_records);$i++){
		    $mxs[$mx_records[$i]] = $mx_weight[$i];
		}
		// Sort them
		asort ($mxs);
		// Since the keys actually hold the data we want, just put those in an array, called records     
		$records = array_keys($mxs);
		// Simply echoes all the stuff in the records array     
		for($i = 0; $i < count($records); $i++){
		    //echo $records[$i];
		}
		$result = $records[0];
		return $result;
	}

        /* resolvenome
           $hostname - String - Host a ser resolvido
           Retorna o IP do host a ser resolvido. */
        function resolvenome($hostname) {
		$result = gethostbyname($hostname);
                return $result;
        }

	/* maillog
           $email - String - Email que sera logado
           $servidor - String - Servidor que fez o envio/tentativa
           $idmensagem - Int - Id da mensagem enviada
           $status - Int - Id do status resultante (0 - Ok, 1 - DNS Error, 2 - Blacklist)
           Loga o envio/tentativa. */
	function maillog($email, $servidor, $idmensagem, $status) {
                $result = $hostname."-resolvido";
                return $result;
        }

        /* atualizaqueue
           $email - String - Email que sera logado
           $status - Int - Id do status resultante (0 - Ok, 1 - DNS Error, 2 - Blacklist)
           Loga o envio/tentativa. */
        function atualizaqueue($email, $status) {
                $result = $hostname."-resolvido";
                return $result;
        }

        /* dumpqueue
           $email - String - Email que sera logado
           $status - Int - Id do status resultante (0 - Ok, 1 - DNS Error, 2 - Blacklist)
           Loga o envio/tentativa. */
        function dumpqueue($email, $status) {
                $result = $hostname."-resolvido";
                return $result;
        }


        /* limpaqueue
           Loga o envio/tentativa. */
        function limpaqueue() {
                $result = $hostname."-resolvido";
                return $result;
        }

        /* enviaemail
           $email - String - Email que sera logado
           $idmensagem - Int - Id da mensagem enviada
           Loga o envio/tentativa. */
        function enviaemail($email, $idmensagem) {
                $mensagem = '<center><table>
<tr><td style="font-face: arial; font-size: 11px; text-align: center; color: #404040;">Caso voc&ecirc; n&atilde;o esteja conseguindo ver esta mensagem, por favor <a style="color: #404040;" href="https://webca.com.br/info/20120507/mail001.htm">clique aqui</a>.
<br />&nbsp;</td></tr>
<tr><td style="text-align: center;"><a href="https://webca.com.br/" alt=""><img src="https://webca.com.br/info/20120507/mail001.png" border="0" alt="" /></a></td></tr>
<tr><td style="font-face: arial; font-size: 11px; text-align: center; color: #404040;">Se voc&ecirc; preferir n&atilde;o receber os informativos da WebCA por e-mail, por favor nos informe <a style="color: #404040;" href="mailto: contato@webca.com.br">clicando aqui</a>.  
&nbsp;&nbsp;</td></tr>
</table></center>';

		require_once('ses.php');
		$ses = new SimpleEmailService('AKIAJJVCSBEOAELNTCWQ', 'yRuG7dWSWaNBZ5rOC6yTnY36hxub47Qgns/ChCmf');
		$m = new SimpleEmailServiceMessage();
		$m->setFrom('WebCA Internet <desenv@webca.com.br>');
		$m->addTo($email);
		$m->setSubject('Confirmacao WebCA ');
		$m->setMessageFromString('This is the <b>message</b> body.');
		//$m->addRawMessageFromString('text', 'This is the text message', 'text/plain');
		$m->addRawMessageFromString('html', $mensagem, 'text/html');
		//$m->addRawMessageFromFile('attachment1', 'my-picture.jpeg', 'image/jpeg');
		$ses->sendEmail($m);

		//$m->clearTo();
		//$m->addTo('next-recipient-address@example.com');
		//$ses->sendEmail($m);

                $result = "concluido";
                return $result;
        }



//	echo nslookupmx("terra.com.br")."\n";
//	echo resolvenome("webca.com.br")."\n";
	echo enviaemail("fernando@webca.com.br")."\n";

?>
