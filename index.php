<?php

//记录请求信息
traceHttp();

// 实际项目token需要有值
define("TOKEN", "");

$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
	$wechatObj->valid();
} else {
	logger('************一次客户端请求开始****************');
	$wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
	public function valid()
	{
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
	}

	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if($tmpStr == $signature){
			return true;
		}else{
			return false;
		}
	}

	public function responseMsg()
	{
		$postStr = file_get_contents("php://input");

		if (!empty($postStr)) {			
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

			$TYPE = trim($postObj->MsgType);
			logger($TYPE);
			switch ($TYPE) {
				// 消息相关
				case 'text':
					$result = $this->receiveText($postObj);
					break;
				case 'image':
					$result = $this->receiveImage($postObj);
					break;
				case 'voice':
					$result = $this->receiveVoice($postObj);
					break;
				case 'shortvideo':
				case 'video':
					$result = $this->receiveVideo($postObj);
					break;
				case 'location':
					$result = $this->receiveLocation($postObj);
					break;
				case 'link':
					$result = $this->receiveLink($postObj);
					break;	

				// 关注、取消关注
				case 'event': 
					$result = $this->receiveEvent($postObj);
					break;

				default:
					$result = '未知消息类型：'.$TYPE;
					break;
			}
			logger($result);
			echo $result;
		} else {
			echo "";
			exit;
		}
	}

	// 接收文本消息
	private function receiveText ($object) {
		if ($object->Content == '抽奖') {
			$content = '<a href="http://lizhiyao.xyz/growing/Lottery/index.html">抽奖</a>';
		} else {
			$content = '你发送的是文本，内容为：'.$object->Content;			
		}
		$result = $this->transmitText($object, $content);
		return $result;		
	}

	// 接收图片消息
	private function receiveImage ($object) {
		$content = '你发送的是图片，地址为：'.$object->PicUrl;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 接收语音消息
	private function receiveVoice ($object) {
		$content = '你发送的是语音，媒体ID为：'.$object->MediaId;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 接收小视频、视频消息
	private function receiveVideo ($object) {
		$content = '你发送的是视频，媒体ID为：'.$object->MediaId;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 接收位置消息
	private function receiveLocation ($object) {
		$content = '你发送的是位置，纬度为：'.$object->Location_X.'；经度为：'.$object->Location_Y.'；缩放级别为：'
			.$object->Scale.'；位置为：'.$object->Label;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 接收链接消息
	private function receiveLink ($object) {
		$content = '你发送的是链接，标题为：'.$object->Title.'；内容为：'.$object->Description.'；链接地址为：'.$object->Url;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 有新用户关注
	private function receiveEvent ($object) {
		$content = '';
		switch ($object->Event) {
			case 'subscribe':
				$content = '欢迎关注李小遥，他也叫李小帅哦~ 现在他在合肥，每天忙着和一群朋友biubiubiu地打豆豆 呼呼~~';
				break;			
			case 'unsubscribe':
				$content = '';
				break;
		}
		$result = $this->transmitText($object, $content);
		return $result;
	}

	// 回复文本消息
	private function transmitText($object, $content) {
		$textTpl = "<xml>
	                <ToUserName><![CDATA[%s]]></ToUserName>
	                <FromUserName><![CDATA[%s]]></FromUserName>
	                <CreateTime>%s</CreateTime>
	                <MsgType><![CDATA[text]]></MsgType>
	                <Content><![CDATA[%s]]></Content>
	                </xml>";
	    $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
	    return $result;
	}
}

function traceHttp()
{
	$wechatIps = [
		'101.226',
		'140.207',
		'103.7'
	];
	$form = 'Unknown IP';
    for ($i = 0; $i < count($wechatIps); $i++) {
    	if (strstr($_SERVER["REMOTE_ADDR"], $wechatIps[i])) {
    		$form = 'FROM WeiXin';
    		break;
    	}
    }

	logger("REMOTE_ADDR:".$_SERVER["REMOTE_ADDR"]."  ".$form);
	logger("QUERY_STRING:".$_SERVER["QUERY_STRING"]);
}

function logger($log_content)
{
	$max_size = 500000;
	$log_filename = "log.xml";
	if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size))
	{
		unlink($log_filename);
	}
	file_put_contents($log_filename, date('Y-m-d H:i:s')."  ".$log_content."\n\n", FILE_APPEND);
}