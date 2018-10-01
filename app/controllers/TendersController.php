<?php

use Phalcon\Queue\Beanstalk;
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
    public function curlDownload($page, $records_p_page) {
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://www.goszakup.gov.kz/ru/search/lots?count_record='.$records_p_page.'&page='.$page.'',
            CURLOPT_USERAGENT => 'Codular Sample cURL Request',
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
    public function downloadAction() {
        $queue = new Beanstalk();
        $page=5000;
        $j=0;
        while ($page>=4500) {
            $j++;
            //$jobId = $queue->put(['page'=>$page], ['priority'=>$j,'delay'=>5, 'ttr'=>'2400']);
            $jobId = $queue->put(['page'=>$page], ['priority'=>$j]);
            echo 'jobID: '.$jobId."<br>";
            //$this->manage_queue($page);
            $page--;
        }
    }
    public function manage_queueAction($page) {
        echo 'start: '.date("H:i:s")."<br>";
        $records_p_page = 100;
        $db = $this->getDi()->getShared('db');
        if (!$db) {
            die('db connection failed');
        }
        require_once ($_SERVER['DOCUMENT_ROOT'].'/simple_html_dom/simple_html_dom.php');
        $resp = $this->curlDownload($page, $records_p_page);
        if( $resp['resp']){
            $html = new simple_html_dom();
            $html->load($resp['resp']);
            $this->db->begin();
            for ($i=1;$i<=$records_p_page;$i++) {
                /*$data           = $html->find('table[id=search-result] tr',$i);
                $lotN           = $data->find('strong[data-info]',0);
                $arr[]          = $lotN;
                echo $lotN."<br>";*/
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
                preg_match("/(.*?)<br.*?\/>/i", $descr_html, $match_descr);
                $descr = $this->cleanup($match_descr[0]);
                $amount = $this->cleanup($data->find('td', 3));
                preg_match("/<\/b(.*?)<br>/i", $td3, $app_arr);
                $app_amount = $this->cleanup($app_arr[0]) ?:0 ;
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
                            echo $message."<br>";
                        }
                        //$this->db->rollback();
                        //echo "rollback <br>";
                    } else {
                        echo $lotLong. '<br>';
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
                //echo $lotLong. '<br>'.$lotShort[0]. '<br>'.$lotName.'<br>'. $cust.'<br/>';
            }
            $this->db->commit();
            $html->clear();
            unset($html);
        } else {
            $err = $resp['curl'];
            die('Error: "' . curl_error($err) . '" - Code: ' . curl_errno($err));
        }
        echo 'end: '.date("H:i:s")."<br>";

    }
}