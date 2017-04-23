<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }
    public function test(){
		//echo 'Hello World!!!';
		$Robot = M('robot');
		$data = $Robot->select();
		var_dump($data);
    }
	public function send1(){
		$routingkey='robot_key';
		//设置连接
		$conn_args = array( 'host'=>'114.55.133.164' , 'port'=> '5672', 'login'=>'rabbit' , 'password'=> 'Rbtr@esit445','vhost' =>'/');
		$conn = new \AMQPConnection($conn_args);
		$conn->connect();
		//创建channel
		$channel = new \AMQPChannel($conn);
		//创建exchange
		$ex = new \AMQPExchange($channel);
		$ex->setName('robot_exchange');//创建名字
		$ex->setType(AMQP_EX_TYPE_DIRECT);
		$ex->setFlags(AMQP_DURABLE);
		$ex->declareExchange();
		//你的消息
		$message = json_encode(array('id'=>'12','name'=>'robot12','do'=>'start'));
		//send msg
		$ex->publish($message,$routingkey);
		echo "send success";
	}
	public function send2(){
		$routingkey='robot_key';
		//设置连接
		$conn_args = array( 'host'=>'114.55.133.164' , 'port'=> '5672', 'login'=>'rabbit' , 'password'=> 'Rbtr@esit445','vhost' =>'/');
		$conn = new \AMQPConnection($conn_args);
		$conn->connect();
		//创建channel
		$channel = new \AMQPChannel($conn);
		//创建exchange
		$ex = new \AMQPExchange($channel);
		$ex->setName('robot_exchange');//创建名字
		$ex->setType(AMQP_EX_TYPE_DIRECT);
		$ex->setFlags(AMQP_DURABLE);
		$ex->declareExchange();
		//你的消息
		$message = json_encode(array('id'=>'12','name'=>'robot12','do'=>'stop'));
		//send msg
		$ex->publish($message,$routingkey);
		echo "send success";
	}
	public function run(){
		$bindingkey='robot_key';
		//连接RabbitMQ
		$conn_args = array( 'host'=>'114.55.133.164' , 'port'=> '5672', 'login'=>'rabbit' , 'password'=> 'Rbtr@esit445','vhost' =>'/');
		$conn = new \AMQPConnection($conn_args);
		$conn->connect();
		//设置queue名称，使用exchange，绑定routingkey
		$channel = new \AMQPChannel($conn);
		$q = new \AMQPQueue($channel);
		$q->setName('robot_queue');
		$q->setFlags(AMQP_DURABLE);
		$q->declare();
		$q->bind('robot_exchange',$bindingkey);
		$i=3600*2;//hour
		while($i>0){
			//消息获取
			$messages = $q->get(AMQP_AUTOACK) ;
			if ($messages){
				$msg = json_decode($messages->getBody(), true );
				var_dump($msg);
				if(isset($msg['do']) && $msg['do'] == 'start'){
					$pid = $this->start();
					$qr_name = $msg['id'].'_'.time();
					rename("/home/vbot/wechat-robot-core/tmp/qr.png", "/data/sharedisk/robot/qrcode/".$qr_name.".png");
					$Robot = M('robot');
					$data['id'] = $msg['id'];
					$data['state'] = 0;
					$data['thread_id'] = $pid;
					$data['qr'] = 'http://114.55.133.164:800/robot/qrcode/'.$qr_name.".png";
					$Robot->save($data);
				}elseif(isset($msg['do']) && $msg['do'] == 'stop'){
					$Robot = M('robot');
					$pid = $Robot->where('id='.$msg['id'])->getField('thread_id');
					var_dump('pid is:'.$pid);
					$this->stop($pid);
					$data['id'] = $msg['id'];
					$data['state'] = 2;
					$data['thread_id'] = 0;
					$data['qr'] = '';
					$Robot->save($data);
				}
			}
			sleep(1);
			$i--;
			echo $i.PHP_EOL;
		}
		$conn->disconnect();
	}
	private function start(){
		$process = proc_open('php /home/vbot/wechat-robot-core/app/runner.php >> /home/vbot/wechat-robot-core/app/runner.log &', array(), $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
		return $pid;
	}
	private function stop($thread_id){
		proc_close(proc_open('kill -9 '.$thread_id, array(), $pipes));
	}
}
