<?php
ini_set('memory_limit', '-1');
header('Access-Control-Allow-Origin: *');

//use Phalcon\Queue\Beanstalk;
require_once(APP_PATH.'/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
Class TendersController extends ControllerBase
{
    public function initialize (){
    }

    public function cleanup ($var) {
        $var = strip_tags($var);
        $var = trim($var," ");
        return $var;
    }
    public function getPagiAction() {
        $db = $this->getDi()->getShared('db');
        if (!$db) {
            die('db connection failed');
        }
        $tenders = new Tenders();
        //$count = count($tenders->find());
        $query = "select count(*) as count from tenders";
        $search =  $this->dispatcher->getParam('search');
        if (!empty($search)) {
            $query = "select count(*) as count from Tenders where lotLong like '%".$search."%'";
        }
        $res = $this->modelsManager->executeQuery($query)->getFirst();
        $count =  $res ['count'];
        $perPage = $this->dispatcher->getParam('perPage');
        $totalPages = ceil( $count / $perPage);
        $pg_arr['count'] = $count;
        $pg_arr['totalPages'] = $totalPages;
        return json_encode($pg_arr);
    }
    public function showDataAction() {
        $db = $this->getDi()->getShared('db');
        if (!$db) {
            die('db connection failed');
        }
        $perPage = $this->dispatcher->getParam('perPage');
        $page = $this->dispatcher->getParam('page');
        $tenders = new Tenders();
        if ( !empty($page) ) {
            $offset = ($page-1)* $perPage;
            $offset < 0 ? 0 : $offset;

            /*foreach ($results as $result) {
                //echo $result->lotShort."<br>";
                //$json= json_encode($result, JSON_UNESCAPED_UNICODE);
            }*/
            $search =  $this->dispatcher->getParam('search');
            if ( !empty($search)) {
                $results = $tenders->find(["conditions" => "lotLong like '%".$search."%'" ]);
            } else {
                $results = $tenders->find(['offset'=> $offset, 'limit'=> $perPage ]);
            }
            return $this->response->setJsonContent($results, JSON_UNESCAPED_UNICODE);
        }
    }

    public function curlDownload($page, $records_p_page) {
        //die($page);
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://www.goszakup.gov.kz/ru/search/lots?count_record='.$records_p_page.'&page='.$page.'',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
            CURLOPT_SSL_VERIFYPEER => false
        ));
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        $ret_arr['resp'] = $resp;
        $ret_arr['curl'] = $curl;
        return $ret_arr;
    }

    public function execute($page)
    {
        $connection = new AMQPStreamConnection(
            'localhost',    #host - host name where the RabbitMQ server is runing
            5672,           #port - port number of the service, 5672 is the default
            'guest',        #user - username to connect to server
            'guest'         #password
        );
        /** @var $channel AMQPChannel */
        $channel = $connection->channel();
        $channel->queue_declare(
            'TendersTime',    #queue name - Queue names may be up to 255 bytes of UTF-8 characters
            false,          #passive - can use this to check whether an exchange exists without modifying the server state
            false,          #durable - make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            false,          #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false           #autodelete - queue is deleted when last consumer unsubscribes
        );

        $pg = new AMQPMessage($page);
        $channel->basic_publish(
            $pg,           #message
            '',             #exchange
            'TendersTime'     #routing key
        );

        $channel->close();
        $connection->close();
    }

    public function downloadAction() {
        $page=5000;
        while ($page>=1) {
            $this->execute($page);
            $page--;
        }
    }
    public function manage_queueAction($page) {
        echo '<b>start:</b> '.date("H:i:s")."<br>";
        $records_p_page = 100;
        $db = $this->getDi()->getShared('db');
        if (!$db) {
            die('db connection failed');
        }
        require_once ($_SERVER['DOCUMENT_ROOT'].'/simple_html_dom/simple_html_dom.php');
        $page = $this->dispatcher->getParam('page');
        $resp = $this->curlDownload($page, $records_p_page);
        if( $resp['resp']){
            $html = new simple_html_dom();
            $html->load($resp['resp']);
            $this->db->begin();
            for ($i=1;$i<=$records_p_page;$i++) {
                $data = $html->find('table[id=search-result] tr', $i);

                $lotLong = $this->cleanup($data->find('strong[data-info]', 0));
                preg_match("/[0-9]{1,100}/i", $lotLong, $lotShort);
                $lotName = $this->cleanup($data->find('a', 0));
                $customer = $data->find('small[class=hidden-xs]', 0);
                // find customer and so on
                preg_match_all("/<\/b>(.*?)<br>/i", $customer, $match_cust);
                $cust = $this->cleanup($match_cust[0][0]);
                $ens_tru = $this->cleanup($match_cust[0][1]);
                $name = $this->cleanup($data->find('td', 2)->find('a', 0));
                $td3 = $data->find('td', 2);
                $descr_html = $td3->find('small[class=hidden-xs]', 0);
                // <br.*?\/> because in original tag extra space ((
                if (!preg_match("/(.*?)<br.*?\/>/i", $descr_html, $match_descr)) {
                    $match_descr[0] = '';
                }
                $descr = $this->cleanup($match_descr[0]);
                $amount = $this->cleanup($data->find('td', 3));
                if (!preg_match("/<\/b(.*?)<br>/i", $td3, $app_arr)) {
                    $app_arr[0] = 0;
                }
                $app_amount = $this->cleanup($app_arr[0]) ;
                $price_per_unit = $this->cleanup($data->find('td', 4)->find('strong', 0));
                $price_per_unit = $this->cleanup(preg_replace('/\s+/', '', $price_per_unit));
                $summ = $this->cleanup($data->find('td', 5)->find('strong', 0));
                $summ = $this->cleanup(preg_replace('/\s+/', '', $summ));
                $way_of_buy = $this->cleanup($data->find('td', 6));
                $sched_way = $this->cleanup($data->find('td', 7));
                $status = $this->cleanup($data->find('td', 8));
                $tenders = new Tenders();
                $check = $tenders->find(['lotLong = ?1', 'bind'=>[1=>$lotLong],]);
                if ( count( $check) == 0 ) {
                    $tenders-> lotLong          =   $lotLong;
                    $tenders-> lotShort         =   $lotShort[0];
                    $tenders-> lotName          =   $lotName;
                    $tenders-> customer         =   $cust;
                    $tenders-> ENS_TRU          =   $ens_tru;
                    $tenders-> name             =   $name;
                    $tenders-> descr            =   $descr;
                    $tenders-> app_amount       =   $app_amount;
                    $tenders-> amount           =   $amount;
                    $tenders-> price_per_unit   =   $price_per_unit;
                    $tenders-> summ             =   $summ;
                    $tenders-> way_of_buy       =   $way_of_buy;
                    $tenders-> sched_way        =   $sched_way;
                    $tenders-> status           =   $status;
                    if  ($tenders->save() == false) {
                        foreach ($tenders->getMessages() as $message) {
                            echo "saving error: ".$message."<br>";
                        }
                        $this->db->rollback();
                    } else {
                        echo "Lot saved: " .$lotLong. '<br>';
                    }
                }
                /*$sql = 'select lotLong from tenders where lotLong = "' . $lotLong . '"';
                $stmt = $db->query($sql);
                if ($stmt->numRows() == 0) {
                    //echo 'does not exist';
                    $sql = 'insert into tenders (lotLong, lotShort, lotName, customer, ENS_TRU, name, descr, app_amount, amount,
                    price_per_unit, summ, way_of_buy, sched_way, status) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                    $success = $db->execute($sql,
                        [$lotLong, $lotShort[0], $lotName, $cust, $ens_tru, $name, $descr, $app_amount,
                            $amount, $price_per_unit, $summ, $way_of_buy, $sched_way, $status]);
                    if (!$success) {
                        $this->db->rollback();
                        echo "rollback <br>";
                        return;
                    } else {
                        echo $lotLong. '<br>';
                    }
                }*/
                //echo $lotLong. '<br>'.$lotShort[0]. '<br>'.$lotName.'<br>'. $cust.'<br>';
            }
            $this->db->commit();
            $html->clear();
            unset($html);
        } else {
            $err = $resp['curl'];
            die('Error: "' . curl_error($err) . '" - Code: ' . curl_errno($err));
        }
        echo '<b>end:</b> '.date("H:i:s")."<br>";
    }
}