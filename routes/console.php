<?php
header("Content-Type:text/html;charset=utf-8");
require_once "simple_html_dom.php";

/**
 *
 */
class scrpy
{
  private $response=array(
    "errorCode"=>400
  );
  static $urls=array("184");
  private $header=array(
    "Content-Type"=>"application/json;charset=utf-8",
    "User-Agent"=>"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.62 Safari/537.36",
    "Referer"=>"http://192.168.220.100/newsedit/newsedit/Entry.do",
    "Accept"=>"*/*",
    "Connection"=>"keep-alive",
    "HOST"=>"192.168.220.100",
    "Pragma"=>"no-cache",
    "X-Requested-With"=>"XMLHttpRequest"
  );

  public function get($url){
    $header = $this->header;
    $curl=curl_init();
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookieb.txt');
    curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookieb.txt');
    curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
    $res= curl_exec($curl);
    curl_close($curl);
      return  $res;
    }
    private function login(){
        $this->get("http://192.168.220.100/newsedit/e5workspace/auth.do?UserCode=lifz&UserPassword=",$this->header);
        $this->dataResponse();
    }
    public function dataResponse(){
        echo json_encode($this->response,JSON_UNESCAPED_UNICODE);
        die;
    }
    public function getDates($rows=0){

      $time=date("Y-m-d",time());

      $d=array(
        "packe.json",
        "packe1.json",
        "packe2.json"
      );

      if ($rows) {
        $rows=20;
        $readyArray=array();
        $preDates=array(
          "text"=>""
        );
        $url="http://192.168.220.100/newsedit/e5workspace/doclist.do?DocLibID=2&FVID=184&FilterID=16,@@DATE%3D{$time}_1@@&ListPage=13&CurrentPage=1&CatTypeID=0&ExtType=0&RuleFormula=&keyword=&tabID=tab_newsedit_department&CountOfPage=20&beginDate=&endDate=";


        $re=$this->get($url,$this->header);

        $_resJson=array();
        $html=new simple_html_dom();
        $tree=$html->load($re);

        if ($re=="") {
          $this->login();
        }else{
              for($i=1;$i<=$rows;$i++){
              if(count($readyArray)==10){
                break;
              }
              $response=array();
              $trees=$tree->find("#listing tr",$i);

            //当前文章时间戳比之前大
              $response["errorCode"]=200;

                  $response["type"]=$trees->find("td",3)->find("img",0)->title;
                $response["text"]=$trees->find("td",4)->find("span",0)->find("a",0)->plaintext;
                $response["name"]=trim($trees->find("td",8)->find("span",0)->plaintext);
                $response["status"]=$trees->find("td",14)->plaintext;
                $response["time"]=trim($trees->find("td",10)->plaintext);
              //更新
              if($i==1){

                $fileres['modeTime']=strtotime(date("Y-",time()).$trees->find("td",12)->plaintext);

                $fileres['pSiteId']=$trees->id;
                $files=fopen("packe.json","w+");
                fwrite($files,json_encode($fileres));
                fclose($files);
              }
        if($preDates['text']===$response["text"] || substr($preDates['text'],0,strlen($preDates['text'])-3) ==  substr($response["text"],0,strlen($response["text"])-3) ){
          continue;
        }else{
          $preDates=$response;
          $readyArray[]=$response;
        }

      }
        echo json_encode($readyArray,JSON_UNESCAPED_UNICODE);
        }
          $html->clear();



  }else{

    foreach (self::$urls as $key => $value) {
    if ($value=="189") {
         $url="http://192.168.220.100/newsedit/e5workspace/doclist.do?DocLibID=4&FVID=189&FilterID=16,@@DATE%3D{$time}_1@@&ListPage=10&CurrentPage=1&CatTypeID=0&ExtType=0&RuleFormula=&keyword=&tabID=tab_newsedit_department&CountOfPage=20&beginDate=&endDate=";

        }else{
          $url="http://192.168.220.100/newsedit/e5workspace/doclist.do?DocLibID=2&FVID=$value&FilterID=22,22,@@DATE%3D{$time}_1@@&ListPage=13&CurrentPage=1&CatTypeID=0&ExtType=0&RuleFormula=&keyword=&tabID=tab_edit_doc_all&CountOfPage=50&beginDate=&endDate=";
        }

      $fileres=$this->getJson($key);

      $re=$this->get($url,$this->header);

      $_resJson=array();
      $html=new simple_html_dom();
      $tree=$html->load($re);
      if ($re=="") {
        $this->login();
        break;
      }else{
        $trees=$tree->find("#listing tr",1);
    if(!@$trees->id){
      continue;
    }
      $id=$trees->id;

          if(isset($fileres["pSiteId"]) && isset($fileres["modeTime"])){
            if($fileres["pSiteId"]<$id){
              if ($value==189) {
                $netTime=date("Y-",time()).$trees->find("td",12)->plaintext;
                if ($fileres['modeTime']<strtotime($netTime)) {
                  $temparty=array();
            $this->response["errorCode"]=200;
            $temparty["type"]=$trees->find("td",2)->find("img",0)->title;
            $temparty["text"]=$trees->find("td",3)->find("span",0)->find("a",0)->plaintext;
            $temparty["name"]=trim($trees->find("td",10)->plaintext);
            $temparty["status"]=$trees->find("td",13)->plaintext;
            $temparty["time"]=trim($trees->find("td",12)->plaintext);
            $this->response["datas"][]=$temparty;
            //更新

            $fileres['modeTime']=strtotime($netTime);
            $fileres['pSiteId']=$trees->id;
            $files=fopen($d[$key],"w+");
            fwrite($files,json_encode($fileres));
            fclose($files);
                }

              }else{
                $netTime=trim($trees->find("td",36)->plaintext);
              //最新稿件
                if($fileres['modeTime']<strtotime($netTime)){
              //当前文章时间戳比之前大
                $this->response["errorCode"]=200;
                $temparty=array();
                $temparty["type"]=$trees->find("td",3)->find("img",0)->title;
                $temparty["text"]=$trees->find("td",4)->find("span",0)->find("a",0)->plaintext;
                $temparty["name"]=trim($trees->find("td",8)->find("span",0)->plaintext);
                $temparty["status"]=$trees->find("td",14)->plaintext;
                $temparty["time"]=trim($trees->find("td",10)->plaintext);
                  $this->response["datas"][]=$temparty;
                //更新
                $fileres['modeTime']=strtotime($netTime);

                $fileres['pSiteId']=$trees->id;

                $files=fopen($d[$key],"w+");
                fwrite($files,json_encode($fileres));
                fclose($files);
              }
              }


            }
          }else{
            $fileres["pSiteId"]="nooo";
            $fileres['modeTime']="ss";
          }
      }
            $html->clear();
    }

    $this->dataResponse();
  }



    }

    private function getJson($index){

      $d=array(
        "packe.json",
        "packe1.json",
        "packe2.json"
      );

      return json_decode(file_get_contents($d[$index]),true);
    }
}

$main=new scrpy();

  if (isset($_GET['rows'])) {
$main->getDates(20);
}else{
  $main->getDates();
}

 ?>
