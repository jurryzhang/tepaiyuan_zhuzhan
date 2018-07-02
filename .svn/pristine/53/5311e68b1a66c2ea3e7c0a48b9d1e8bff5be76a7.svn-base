<?php
/**
 * @copyright Copyright(c) 2014 aircheng.com
 * @file area.php
 * @brief 省市地区调用函数
 * @author nswe
 * @date 2014/8/6 20:46:52
 * @version 2.6
 * @note
 */

 /**
 * @class area
 * @brief 省市地区调用函数
 */
class area
{
	/**
	 * @brief 根据传入的地域ID获取地域名称，获取的名称是根据ID依次获取的
	 * @param int 地域ID 匿名参数可以多个id
	 * @return array
	 */
	public static function name()
	{
		$result     = array();
		$paramArray = func_get_args();

		//根据参数ID初始化数组值
		foreach($paramArray as $key => $val)
		{
			$result[$val] = "";
		}

		$areaDB     = new IModel('areas');
		$areaData   = $areaDB->query("area_id in (".trim(join(',',$paramArray),",").")");

		foreach($areaData as $key => $value)
		{
			$result[$value['area_id']] = $value['area_name'];
		}
		return $result;
	}

	public static function name_getid($paramArray)
	{
		$result=array();
		$str=trim("'".join('\',\'',$paramArray)."'",",");
		
		$areaDB     = new IModel('areas');
		$areaData   = $areaDB->query("area_name in (".$str.")");
		// echo $areaDB->getSql();
		$provinceid=self::getAreaId($areaData,$paramArray['province']);//省
		$result['cookie']['province']['id']=$provinceid;
		$result['cookie']['province']['name']=$paramArray['province'];

       	$cityid=self::getAreaId($areaData,$paramArray['city'],1,$provinceid);//市
       	$result['cookie']['city']['id']=$provinceid;
		$result['cookie']['city']['name']=$paramArray['city'];

       	$districtid=self::getAreaId($areaData,$paramArray['district'],2,$cityid);//区县
       	$result['cookie']['district']['id']=$provinceid;
		$result['cookie']['district']['name']=$paramArray['district'];

		$districtid=self::getAreaId($areaData,$paramArray['street'],3,$cityid);//街道
       	$result['cookie']['street']['id']=$provinceid;
		$result['cookie']['street']['name']=$paramArray['street'];


		$result['cookie']['market']['id']=$cityid;
		$result['cookie']['market']['name']='city';

		return $result;
	}

	public function  getAreaId($data,$name,$type=0,$parent=0)
	{
		$area_id=0;
		if($data)
		{
			foreach ($data as $k => $v) {
				if($v['area_name']==$name && $v['parent_id']==$parent)
				{
					$areas_city=[110000,310000,120000,500000];
					$parent_id=$v['parent_id'];
					if(in_array($parent_id, $areas_city) && $v['area_type']==$type)
					{
						$area_id=$v['area_id'];
					}else{
						$area_id=$v['area_id'];
					}
					continue;
				}
			}
		}
		return $area_id;
	}


	public function getTraceByName($city='',$address='')
    {
        $result = array();
        $result['lat'] = 0.00;
        $result['lng'] = 0.00;
        $ak = $this->_siteConfig->baidu_map_key;
        $url ="http://api.map.baidu.com/geocoder/v2/?callback=renderOption&output=json&address=".$address."&city=".$city."&ak=".$ak;
        // echo $url.'<br>';
        $data = file_get_contents($url);
        if($data)
        {
        	$data = str_replace('renderOption&&renderOption(', '', $data);
	        $data = str_replace(')', '', $data);
	        $data = json_decode($data,true);

	        if (!empty($data) && $data['status'] == 0) {
	            $result['lat'] = $data['result']['location']['lat'];
	            $result['lng'] = $data['result']['location']['lng'];
	        } 
        }else{
        	$result['lat'] = 0;
	       	$result['lng'] = 0;
        }
        
        return $result;
    }
    //根据 经纬度 获取距离
    public function getDistance($lat1=0,$lng1=0,$lat2=0,$lng2=0)
    {
        $result = array();
        $result['distance'] = 0.00;//距离 米
        $result['duration'] = 0.00;//时间 分钟
        $ak = $this->_siteConfig->baidu_map_key;
        $url = 'http://api.map.baidu.com/routematrix/v2/driving?output=json&origins='.$lat1.','.$lng1.'&destinations='.$lat2.','.$lng2.'&ak='.$ak;
        // echo $url.'<br>';exit();
        $data = file_get_contents($url);
        if($data)
        {
        	$data = json_decode($data,true);
	        if (!empty($data) && $data['status'] == 0) {
	            // $result['distance'] = $data['result'][0]['distance']['text'];
	            // $result['duration'] = $data['result'][0]['duration']['text'];
	            // $result['distance'] = $data['result'][0]['distance']['value'];
	            $distance=$data['result'][0]['distance'];
	        }
        }else{
        	$distance=array();
        }

        
        return $distance;
    }

}