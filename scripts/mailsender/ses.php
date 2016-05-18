<?php
/**
*
* Copyright (c) 2011, Dan Myers.
* Parts copyright (c) 2008, Donovan Schonknecht.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* This is a modified BSD license (the third clause has been removed).
* The BSD license may be found here:
* http://www.opensource.org/licenses/bsd-license.php
*
* Amazon Simple Email Service is a trademark of Amazon.com, Inc. or its affiliates.
*
* SimpleEmailService is based on Donovan Schonknecht's Amazon S3 PHP class, found here:
* http://undesigned.org.za/2007/10/22/amazon-s3-php-class
*
* SendRawEmail support, support for arbitrary email headers (with SendRawEmail) and
* performance optimizations added by Antone Roundy.
* http://WhiteHatCrew.com/blog/free-php-class-for-sending-email-through-amazon-ses/
*
*/

/**
* Antone Roundy version 2, based on:
*
* Amazon SimpleEmailService PHP class
*
* @link http://sourceforge.net/projects/php-aws-ses/
* version 0.8.2
*
*/
class SimpleEmailService
{
	protected $__accessKey; // AWS Access key
	protected $__secretKey; // AWS Secret key
	protected $__host;

	public function getAccessKey() { return $this->__accessKey; }
	public function getSecretKey() { return $this->__secretKey; }
	public function getHost() { return $this->__host; }

	protected $__verifyHost = 1;
	protected $__verifyPeer = 1;

	// verifyHost and verifyPeer determine whether curl verifies ssl certificates.
	// It may be necessary to disable these checks on certain systems.
	// These only have an effect if SSL is enabled.
	public function verifyHost() { return $this->__verifyHost; }
	public function enableVerifyHost($enable = true) { $this->__verifyHost = $enable; }

	public function verifyPeer() { return $this->__verifyPeer; }
	public function enableVerifyPeer($enable = true) { $this->__verifyPeer = $enable; }

	/**
	* Constructor
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $host = 'email.us-east-1.amazonaws.com') {
		if ($accessKey !== null && $secretKey !== null) {
			$this->setAuth($accessKey, $secretKey);
		}
		$this->__host = $host;
	}

	/**
	* Set AWS access key and secret key
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public function setAuth($accessKey, $secretKey) {
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;
	}

	/**
	* Lists the email addresses that have been verified and can be used as the 'From' address
	* 
	* @return An array containing two items: a list of verified email addresses, and the request id.
	*/
	public function listVerifiedEmailAddresses() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'ListVerifiedEmailAddresses');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('listVerifiedEmailAddresses', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$addresses = array();
		foreach($rest->body->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
			$addresses[] = (string)$address;
		}

		$response['Addresses'] = $addresses;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Requests verification of the provided email address, so it can be used
	* as the 'From' address when sending emails through SimpleEmailService.
	*
	* After submitting this request, you should receive a verification email
	* from Amazon at the specified address containing instructions to follow.
	*
	* @param string email The email address to get verified
	* @return The request id for this request.
	*/
	public function verifyEmailAddress($email) {
		$rest = new SimpleEmailServiceRequest($this, 'POST');
		$rest->setParameter('Action', 'VerifyEmailAddress');
		$rest->setParameter('EmailAddress', $email);

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('verifyEmailAddress', $rest->error);
			return false;
		}

		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Removes the specified email address from the list of verified addresses.
	*
	* @param string email The email address to remove
	* @return The request id for this request.
	*/
	public function deleteVerifiedEmailAddress($email) {
		$rest = new SimpleEmailServiceRequest($this, 'DELETE');
		$rest->setParameter('Action', 'DeleteVerifiedEmailAddress');
		$rest->setParameter('EmailAddress', $email);

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('deleteVerifiedEmailAddress', $rest->error);
			return false;
		}

		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Retrieves information on the current activity limits for this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendQuota.html
	*
	* @return An array containing information on this account's activity limits.
	*/
	public function getSendQuota() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'GetSendQuota');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('getSendQuota', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$response['Max24HourSend'] = (string)$rest->body->GetSendQuotaResult->Max24HourSend;
		$response['MaxSendRate'] = (string)$rest->body->GetSendQuotaResult->MaxSendRate;
		$response['SentLast24Hours'] = (string)$rest->body->GetSendQuotaResult->SentLast24Hours;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Retrieves statistics for the last two weeks of activity on this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendStatistics.html
	*
	* @return An array of activity statistics.  Each array item covers a 15-minute period.
	*/
	public function getSendStatistics() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'GetSendStatistics');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('getSendStatistics', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$datapoints = array();
		foreach($rest->body->GetSendStatisticsResult->SendDataPoints->member as $datapoint) {
			$p = array();
			$p['Bounces'] = (string)$datapoint->Bounces;
			$p['Complaints'] = (string)$datapoint->Complaints;
			$p['DeliveryAttempts'] = (string)$datapoint->DeliveryAttempts;
			$p['Rejects'] = (string)$datapoint->Rejects;
			$p['Timestamp'] = (string)$datapoint->Timestamp;

			$datapoints[] = $p;
		}

		$response['SendDataPoints'] = $datapoints;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Contributed by Antone Roundy
	* This function is not called directly. If you've added raw parts, sendEmail will call this function automatically.
	*/
	private function sendRawEmail(&$rest, &$sesMessage) {
		$rest->setParameter('Action', 'SendRawEmail');

		$rawMessage='';
		
		foreach($sesMessage->to as $to) $rawMessage.="To: $to\n";
		foreach($sesMessage->cc as $to) $rawMessage.="CC: $to\n";
		foreach($sesMessage->bcc as $to) $rawMessage.="BCC: $to\n";
		if(is_array($sesMessage->replyto)) foreach($sesMessage->replyto as $to) $rawMessage.="Reply-To: $to\n";
		$rawMessage.='From: '.$sesMessage->from."\n";
		if(isset($sesMessage->returnpath{0})) $rawMessage.='Return-Path: '.$sesMessage->returnpath."\n";

		if(isset($sesMessage->subject{0})) {
			$rawMessage.='Subject: '.(preg_match('/[0x80-0xFF]/',$sesMessage->subject)?
				('=?'.(isset($sesMessage->subjectCharset{0})?$sesMessage->subjectCharset:'ISO-8859-1').'?B?'.
					base64_encode($sesMessage->subject).'?='):
				$sesMessage->subject).
				"\n";
		}
		$rawMessage.='Date: '.gmdate('d M Y H:i:s')." -0000\n";
		foreach ($sesMessage->emailHeaders as $name=>$value) if (isset($value{0})) $rawMessage.="$name: $value\n";
		
		$hasWhat=0;
		$htmlPart=$textPart=-1;
		$otherParts=array();
		for ($i=count($sesMessage->rawMessageParts)-1;$i>=0;$i--) {
			if (substr($sesMessage->rawMessageParts[$i]['type'],0,9)=='text/html') {
				$hasWhat |= 1;
				$htmlPart = $i;
			} else if (substr($sesMessage->rawMessageParts[$i]['type'],0,10)=='text/plain') {
				$hasWhat |= 2;
				$textPart = $i;
			} else {
				$hasWhat |= 4;
				$otherParts[]=$i;
			}
		}
		if ($hasWhat==3) $mptype='alternative';
		else if (($hasWhat>4)||(count($sesMessage->rawMessageParts)>1)) $mptype='mixed';
		else $mptype='';
		
		if (isset($mptype{0})) {
			$mixedbound='Mixed_MIME_Boundary';
			$altbound='Alt_MIME_Boundary';
			$mainbound=($hasWhat==3)?$altbound:$mixedbound;
			$rawMessage.="Mime-Version: 1.0\n".
				"Content-Type: multipart/$mptype; boundary=\"$mainbound\"\n".
				"\n";
			
			$rawMessage.="--$mainbound\n";
			if (($hasWhat&7) == 7)
				$rawMessage.="Content-Type: multipart/alternative; boundary=\"$altbound\"\n\n--$altbound\n";
				
			if ($hasWhat&1) $rawMessage.=
				'Content-Type: '.$sesMessage->rawMessageParts[$htmlPart]['type']."\n".
				($sesMessage->rawMessageParts[$htmlPart]['isBase64']?"Content-Transfer-Encoding: base64\n":'').
				"\n".
				$sesMessage->rawMessageParts[$htmlPart]['data'].
				"\n";
			
			if (($hasWhat&7) == 7) $rawMessage.="--$altbound\n";
			else if (($hasWhat&3) == 3) $rawMessage.="--$mainbound\n";

			if ($hasWhat&2) $rawMessage.=
				'Content-Type: '.$sesMessage->rawMessageParts[$textPart]['type']."\n".
				($sesMessage->rawMessageParts[$textPart]['isBase64']?"Content-Transfer-Encoding: base64\n":'').
				"\n".
				$sesMessage->rawMessageParts[$textPart]['data'].
				"\n";

			if (($hasWhat&7) == 7) $rawMessage.="--$altbound--\n";
			if ($hasWhat&3) $rawMessage.="--$mainbound\n";
			
			for ($i=0,$j=count($otherParts);$i<$j;$i++) $rawMessage.=
				'Content-Type: '.$sesMessage->rawMessageParts[$otherParts[$i]]['type'].(
					isset($sesMessage->rawMessageParts[$otherParts[$i]]['filename']{0})?
						('; name="'.$sesMessage->rawMessageParts[$otherParts[$i]]['filename']."\"\n".
						'Content-Disposition: Attachment; filename="'.$sesMessage->rawMessageParts[$otherParts[$i]]['filename']."\""):''
					)."\n".
				($sesMessage->rawMessageParts[$otherParts[$i]]['isBase64']?"Content-Transfer-Encoding: base64\n":'').
				"\n".
				$sesMessage->rawMessageParts[$otherParts[$i]]['data'].
				"\n--$mainbound".
				(($i==($j-1))?'--':'').
				"\n";
		} else $rawMessage.="Content-Type: ".$sesMessage->rawMessageParts[0]['type']."\n".
			($sesMessage->rawMessageParts[0]['isBase64']?"Content-Transfer-Encoding: base64\n":'').
			"\n".
			$sesMessage->rawMessageParts[0]['data'].
			"\n";
		$rest->setParameter('RawMessage.Data', base64_encode($rawMessage));
	}

	/**
	* Given a SimpleEmailServiceMessage object, submits the message to the service for sending.
	*
	* @return An array containing the unique identifier for this message and a separate request id.
	*         Returns false if the provided message is missing any required fields.
	*/
	public function sendEmail($sesMessage) {
		if(!$sesMessage->validate()) {
			$this->__triggerError('sendEmail', 'Message failed validation.');
			return false;
		}

		$rest = new SimpleEmailServiceRequest($this, 'POST');

		if ($isRaw=$sesMessage->isRaw()) $this->sendRawEmail($rest,$sesMessage);
		else {
			$rest->setParameter('Action', 'SendEmail');
	
			$i = 1;
			foreach($sesMessage->to as $to) {
				$rest->setParameter('Destination.ToAddresses.member.'.$i, $to);
				$i++;
			}
	
			if(is_array($sesMessage->cc)) {
				$i = 1;
				foreach($sesMessage->cc as $cc) {
					$rest->setParameter('Destination.CcAddresses.member.'.$i, $cc);
					$i++;
				}
			}
	
			if(is_array($sesMessage->bcc)) {
				$i = 1;
				foreach($sesMessage->bcc as $bcc) {
					$rest->setParameter('Destination.BccAddresses.member.'.$i, $bcc);
					$i++;
				}
			}
	
			if(is_array($sesMessage->replyto)) {
				$i = 1;
				foreach($sesMessage->replyto as $replyto) {
					$rest->setParameter('ReplyToAddresses.member.'.$i, $replyto);
					$i++;
				}
			}
	
			$rest->setParameter('Source', $sesMessage->from);
	
			if(isset($sesMessage->returnpath{0})) {
				$rest->setParameter('ReturnPath', $sesMessage->returnpath);
			}
	
			if(isset($sesMessage->subject{0})) {
				$rest->setParameter('Message.Subject.Data', $sesMessage->subject);
				if(isset($sesMessage->subjectCharset{0})) {
					$rest->setParameter('Message.Subject.Charset', $sesMessage->subjectCharset);
				}
			}
	
			if(isset($sesMessage->messagetext{0})) {
				$rest->setParameter('Message.Body.Text.Data', $sesMessage->messagetext);
				if(isset($sesMessage->messageTextCharset{0})) {
					$rest->setParameter('Message.Body.Text.Charset', $sesMessage->messageTextCharset);
				}
			}
	
			if(isset($sesMessage->messagehtml{0})) {
				$rest->setParameter('Message.Body.Html.Data', $sesMessage->messagehtml);
				if(isset($sesMessage->messageHtmlCharset{0})) {
					$rest->setParameter('Message.Body.Html.Charset', $sesMessage->messageHtmlCharset);
				}
			}
		}

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('sendEmail', $rest->error);
			return false;
		}

		$response['MessageId'] = (string)($isRaw?$rest->body->SendRawEmailResult->MessageId:
			$rest->body->SendEmailResult->MessageId);
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Trigger an error message
	*
	* @internal Used by member functions to output errors
	* @param array $error Array containing error information
	* @return string
	*/
	public function __triggerError($functionname, $error)
	{
		if($error == false) {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error, but no description given", $functionname), E_USER_WARNING);
		}
		else if(isset($error['curl']) && $error['curl'])
		{
			trigger_error(sprintf("SimpleEmailService::%s(): %s %s", $functionname, $error['code'], $error['message']), E_USER_WARNING);
		}
		else if(isset($error['Error']))
		{
			$e = $error['Error'];
			$message = sprintf("SimpleEmailService::%s(): %s - %s: %s\nRequest Id: %s\n", $functionname, $e['Type'], $e['Code'], $e['Message'], $error['RequestId']);
			trigger_error($message, E_USER_WARNING);
		}
		else {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error: %s", $functionname, $error), E_USER_WARNING);
		}
	}
}

final class SimpleEmailServiceRequest
{
	private $ses, $verb, $parameters = array();
	public $response;

	/**
	* Constructor
	*
	* @param string $ses The SimpleEmailService object making this request
	* @param string $action action
	* @param string $verb HTTP verb
	* @return mixed
	*/
	function __construct($ses, $verb) {
		$this->ses = $ses;
		$this->verb = $verb;
		$this->response = new STDClass;
		$this->response->error = false;
	}

	/**
	* Set request parameter
	*
	* @param string  $key Key
	* @param string  $value Value
	* @param boolean $replace Whether to replace the key if it already exists (default true)
	* @return void
	*/
	public function setParameter($key, $value, $replace = true) {
		if(!$replace && isset($this->parameters[$key]))
		{
			$temp = (array)($this->parameters[$key]);
			$temp[] = $value;
			$this->parameters[$key] = $temp;
		}
		else
		{
			$this->parameters[$key] = $value;
		}
	}

	/**
	* Get the response
	*
	* @return object | false
	*/
	public function getResponse() {

		$params = array();
		foreach ($this->parameters as $var => $value)
		{
			if(is_array($value))
			{
				foreach($value as $v)
				{
					$params[] = $var.'='.$this->__customUrlEncode($v);
				}
			}
			else
			{
				$params[] = $var.'='.$this->__customUrlEncode($value);
			}
		}

		sort($params, SORT_STRING);

		// must be in format 'Sun, 06 Nov 1994 08:49:37 GMT'
		$date = gmdate('D, d M Y H:i:s e');

		$query = implode('&', $params);

		$headers = array();
		$headers[] = 'Date: '.$date;
		$headers[] = 'Host: '.$this->ses->getHost();

		$auth = 'AWS3-HTTPS AWSAccessKeyId='.$this->ses->getAccessKey();
		$auth .= ',Algorithm=HmacSHA256,Signature='.$this->__getSignature($date);
		$headers[] = 'X-Amzn-Authorization: '.$auth;

		$url = 'https://'.$this->ses->getHost().'/';

		// Basic setup
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SimpleEmailService/php');

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($this->ses->verifyHost() ? 1 : 0));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->ses->verifyPeer() ? 1 : 0));

		// Request types
		switch ($this->verb) {
			case 'GET':
				$url .= '?'.$query;
				break;
			case 'POST':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			break;
			case 'DELETE':
				$url .= '?'.$query;
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
			default: break;
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Execute, grab errors
		if (curl_exec($curl)) {
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		} else {
			$this->response->error = array(
				'curl' => true,
				'code' => curl_errno($curl),
				'message' => curl_error($curl),
				'resource' => $this->resource
			);
		}

		@curl_close($curl);

		// Parse body into XML
		if ($this->response->error === false && isset($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab SES errors
			if (!in_array($this->response->code, array(200, 201, 202, 204))
				&& isset($this->response->body->Error)) {
				$error = $this->response->body->Error;
				$output = array();
				$output['curl'] = false;
				$output['Error'] = array();
				$output['Error']['Type'] = (string)$error->Type;
				$output['Error']['Code'] = (string)$error->Code;
				$output['Error']['Message'] = (string)$error->Message;
				$output['RequestId'] = (string)$this->response->body->RequestId;

				$this->response->error = $output;
				unset($this->response->body);
			}
		}

		return $this->response;
	}

	/**
	* CURL write callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data) {
		$this->response->body .= $data;
		return strlen($data);
	}

	/**
	* Contributed by afx114
	* URL encode the parameters as per http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?Query_QueryAuth.html
	* PHP's rawurlencode() follows RFC 1738, not RFC 3986 as required by Amazon. The only difference is the tilde (~), so convert it back after rawurlencode
	* See: http://www.morganney.com/blog/API/AWS-Product-Advertising-API-Requires-a-Signed-Request.php
	*
	* @param string $var String to encode
	* @return string
	*/
	private function __customUrlEncode($var) {
		return str_replace('%7E', '~', rawurlencode($var));
	}

	/**
	* Generate the auth string using Hmac-SHA256
	*
	* @internal Used by SimpleDBRequest::getResponse()
	* @param string $string String to sign
	* @return string
	*/
	private function __getSignature($string) {
		return base64_encode(hash_hmac('sha256', $string, $this->ses->getSecretKey(), true));
	}
}


final class SimpleEmailServiceMessage {

	// these are public for convenience only
	// these are not to be used outside of the SimpleEmailService class!
	public $to, $cc, $bcc, $replyto;
	public $from, $returnpath;
	public $subject, $messagetext, $messagehtml;
	public $subjectCharset, $messageTextCharset, $messageHtmlCharset;
	public $rawMessageParts, $emailHeaders;

	function __construct() {
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->replyto = array();

		$this->from = '';
		$this->returnpath = '';

		$this->subject = '';
		$this->messagetext = '';
		$this->messagehtml = '';

		$this->subjectCharset = '';
		$this->messageTextCharset = '';
		$this->messageHtmlCharset = '';
		
		$this->rawMessageParts=array();
		
		$this->emailHeaders=array();
	}

	function isRaw() {
		return count($this->rawMessageParts)?true:false;
	}

	/**
	* addTo, addCC, addBCC, and addReplyTo have the following behavior:
	* If a single address is passed, it is appended to the current list of addresses.
	* If an array of addresses is passed, that array is merged into the current list.
	*/
	function addTo($to) {
		if(!is_array($to)) {
			$this->to[] = $to;
		}
		else {
			$this->to = array_merge($this->to, $to);
		}
	}

	function addCC($cc) {
		if(!is_array($cc)) {
			$this->cc[] = $cc;
		}
		else {
			$this->cc = array_merge($this->cc, $cc);
		}
	}

	function addBCC($bcc) {
		if(!is_array($bcc)) {
			$this->bcc[] = $bcc;
		}
		else {
			$this->bcc = array_merge($this->bcc, $bcc);
		}
	}
	
	/**
	* Contributed by Antone Roundy
	* The following functions reset the recipient arrays, enabling you to use the same SimpleEmailServiceMessage
	* for multiple API calls without having to reconstruct the entire email each time.
	*/
	function clearTo() {
		$this->to = array();
	}
	
	function clearCC() {
		$this->cc = array();
	}
	
	function clearBCC() {
		$this->bcc = array();
	}
	
	function clearRecipients() {
		$this->clearTo();
		$this->clearCC();
		$this->clearBCC();
	}
	function setEmailHeader($name,$value) {
		if (isset($value{0})) $this->emailHeaders[$name] = $value;
		else if (isset($this->emailHeaders[$name])) unset($this->emailHeaders[$name]);
	}
	function clearEmailHeaders() {
		$this->emailHeaders=array();
	}

	function addReplyTo($replyto) {
		if(!is_array($replyto)) {
			$this->replyto[] = $replyto;
		}
		else {
			$this->replyto = array_merge($this->replyto, $replyto);
		}
	}

	function setFrom($from) {
		$this->from = $from;
	}

	function setReturnPath($returnpath) {
		$this->returnpath = $returnpath;
	}

	function setSubject($subject) {
		$this->subject = $subject;
	}

	function setSubjectCharset($charset) {
		$this->subjectCharset = $charset;
	}

	function setMessageFromString($text, $html = '') {
		$this->messagetext = $text;
		$this->messagehtml = $html;
	}

	function setMessageFromFile($textfile, $htmlfile = '') {
		if(file_exists($textfile) && is_file($textfile) && is_readable($textfile)) {
			$this->messagetext = file_get_contents($textfile);
		} else {
			$this->messagetext = '';
		}
		if(file_exists($htmlfile) && is_file($htmlfile) && is_readable($htmlfile)) {
			$this->messagehtml = file_get_contents($htmlfile);
		} else {
			$this->messagehtml = '';
		}
	}

	function setMessageFromURL($texturl, $htmlurl = '') {
		if(isset($texturl{0})) {
			$this->messagetext = file_get_contents($texturl);
		} else {
			$this->messagetext = '';
		}
		if(isset($htmlurl{0})) {
			$this->messagehtml = file_get_contents($htmlurl);
		} else {
			$this->messagehtml = '';
		}
	}
	
	/**
	* Contributed by Antone Roundy
	* The following functions add and remove message parts for use with SendRawMessage.
	* $id is not part of the SES API. It is used to enable easy replacement of a message part when using the same
	*    SimpleEmailServiceMessage with multiple API calls (for example, to replace the message part with a
	*    personalized message for each recipient while keeping attachments the same).
	* $type is the MIME type, including any required parameters other than a filename. Do not end $type with a semicolon.
	*    Use "text/plain" for plain text, and "text/html" for HTML, optionally with parameters.
	* $filename is used if the part is an attachment. Leave it blank or omit it for text parts.
	*/
	function addRawMessageFromString($id,$data,$type='',$filename='') {
		if (!isset($type{0})) $type='application/octet-stream';
		if (substr($type,0,5)=='text/') {
			if (preg_match('/[\\x80-\\xFF]/',$data)) $doBase64=1;
			else {
				$lines=explode("\n",preg_replace("/[\r\n]+/","\n",$data));
				for ($i=count($lines)-1;$i>=0;$i--) if (strlen($lines[$i])>997) {
					$doBase64=1;
					break;
				}
				if ($i==-1) $doBase64=0;
			}
		} else $doBase64=1;
		for ($i=count($this->rawMessageParts)-1;$i>=0;$i--)
			if ($this->rawMessageParts[$i]['id']==$id) {
				$this->rawMessageParts[$i]=array(
					'id'=>$id,
					'type'=>$type,
					'filename'=>$filename,
					'isBase64'=>$doBase64,
					'data'=>$doBase64?chunk_split(base64_encode($data),76):$data);
				return;
			}
		$this->rawMessageParts[]=array(
			'id'=>$id,
			'type'=>$type,
			'filename'=>$filename,
			'isBase64'=>$doBase64,
			'data'=>$doBase64?chunk_split(base64_encode($data),76):$data);
	}
	function addRawMessageFromFile($id,$file,$type='',$filename='') {
		if (!isset($filename{0})) $filename=basename($file); 
		if(isset($file{0}) && file_exists($file) && is_file($file) && is_readable($file) && (($temp=file_get_contents($file))!==false))
			$this->addRawMessageFromString($id,$temp,$type,$filename);
	}
	function addRawMessageFromURL($id,$url,$type='',$filename='') {
		if(isset($url{0}) && (($temp=file_get_contents($file))!==false))
			$this->addRawMessageFromString($id,$temp,$type,$filename);
	}
	
	function removeRawMessagePart($id) {
		for ($i=count($this->rawMessageParts)-1;$i>=0;$i--)
			if ($this->rawMessageParts[$i]['id']==$id) {
				array_splice($this->rawMessageParts,$i,1);
				return;
			}
	}
	
	function setMessageCharset($textCharset, $htmlCharset = '') {
		$this->messageTextCharset = $textCharset;
		$this->messageHtmlCharset = $htmlCharset;
	}

	/**
	* Validates whether the message object has sufficient information to submit a request to SES.
	* This does not guarantee the message will arrive, nor that the request will succeed;
	* instead, it makes sure that no required fields are missing.
	*
	* This is used internally before attempting a SendEmail or SendRawEmail request,
	* but it can be used outside of this file if verification is desired.
	* May be useful if e.g. the data is being populated from a form; developers can generally
	* use this function to verify completeness instead of writing custom logic.
	*
	* @return boolean
	*/
	public function validate() {
		if((!count($this->to)) ||
			((count($this->to) + count($this->cc) + count($this->bcc)) > 50))
			return false;
		if(!isset($this->from{0}))
			return false;
		// messages require at least one of: subject, messagetext, messagehtml or a raw message part
		if((!isset($this->subject{0}))
			&& (!isset($this->messagetext{0}))
			&& (!isset($this->messagehtml{0}))
			&& !count($this->rawMessageParts)
			)
		{
			return false;
		}

		return true;
	}
}
