<?php
/**
* @user : wangying
* @date : 2016年12月17日
* @desc : 从百度地图获取都江堰全景图
**/
include_once 'simple_html_dom.php';
class getPanos
{
	/**
	 * @param unknown $path
	 * @desc:将百度地图上的全景图片下载下来，并拼接成全景图
	 */
	public function downloadPanos($path)
	{
		$dom=new simple_html_dom();
		$dom->load_file($path);
		$divs=$dom->find('.panoAlbum-item-wraper');
		$return=array();
		foreach ($divs as $div)
		{
			$children=$div->children;
			//span-title
			$divChid=$children[1];
			$span=$divChid->children[1];
			$title=$span->attr['title'];
			//img-src
			$imgAttrs=$children[0]->attr;
			$url=$imgAttrs['src'];
			$url=preg_replace('/width=198/', 'width=1024', $url);
			$url=preg_replace('/height=108/', 'height=1024', $url);
			$url=preg_replace('/&amp;/', '&', $url);
			//下载img
// 			$filename="0_0.png";
// 			$this->savePng($url, $filename,'imgs/'.$title.'/');
			//下载全景图
			preg_match('/&panoid=(.*?)&/', $url,$return);
			$panoid=$return[1];
			$this->saveAndJoin($panoid,'imgs/'.$title.'/');
		}
	}
	/**
	 * @param unknown $sid 全景图的panoid
	 * @param unknown $dir 每个场景的目录(imgs下的场景目录下)
	 * @desc:
	 */
	protected function saveAndJoin($sid,$dir)
	{
		$panoDir=$dir.'panos/';
		$panoPng=imagecreatetruecolor(512*8, 512*4);
		for($i=0;$i<=3;$i++)
		{
			for ($y=0;$y<=7;$y++)
			{
				$url="http://pcsv1.map.bdimg.com/?qt=pdata&sid=".$sid."&pos=".$i."_".$y."&z=4&udt=20160824";
				$this->savePng($url, $i."_".$y.'.png',$panoDir);
				$name=$panoDir.$i."_".$y.'.png';
				$copyImg=imagecreatefromjpeg(iconv('utf-8', 'gbk', $name));
				$dt_y=$i*512;
				$dt_x=$y*512;
				imagecopy($panoPng, $copyImg, $dt_x, $dt_y, 0, 0, 512, 512);
			}
		}
		imagepng($panoPng,iconv('utf-8', 'gbk', $dir.'pnao.png'));
	}
	/**
	 * @param unknown $path
	 * @desc:只是下载全景图片
	 */
	public function get_html($path)
	{
		 $dom=new simple_html_dom();
		$dom->load_file($path);
		$divs=$dom->find('.panoAlbum-item-wraper');
		$return=array();
		 foreach ($divs as $div)
		{
			$children=$div->children;
			//span-title
			$divChid=$children[1];
			$span=$divChid->children[1];
			$title=$span->attr['title'];
			//img-src
			$imgAttrs=$children[0]->attr;
			$url=$imgAttrs['src'];
			$url=preg_replace('/width=198/', 'width=1024', $url);
			$url=preg_replace('/height=108/', 'height=1024', $url);
			$url=preg_replace('/&amp;/', '&', $url);
			//下载img
			$filename="0_0.png";
			$this->savePng($url, $filename,'imgs/'.$title.'/');
			//下载全景图
			preg_match('/&panoid=(.*?)&/', $url,$return);
			$panoid=$return[1];
			$this->savePanos($panoid,'imgs/'.$title.'/panos/');
		}  
	}
	/**
	 * @param string $sid 
	 * @param unknown $dir
	 * @desc:保存全景图片
	 */
	protected function savePanos($sid,$dir)
	{
		for($i=0;$i<=3;$i++)
		{
			for ($y=0;$y<=7;$y++)
			{
				$url="http://pcsv1.map.bdimg.com/?qt=pdata&sid=".$sid."&pos=".$i."_".$y."&z=4&udt=20160824";
				$this->savePng($url, $i."_".$y.'.png',$dir);
			}
		}
		
	}
	/**
	 * @param unknown $url
	 * @param unknown $filename
	 * @param string $dir
	 * @param number $type
	 * @return boolean|string
	 * @desc:从URL上下载图片至本地
	 */
	protected function savePng($url,$filename,$dir="imgs/",$type=0)
	{
		if($url==''){return false;}
		//文件保存路径
		if($type){
			$ch=curl_init();
			$timeout=5;
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			$img=curl_exec($ch);
			curl_close($ch);
		}else{
			ob_start();
			readfile($url);
			$img=ob_get_contents();
			ob_end_clean();
		}
		$size=strlen($img);
		if(!file_exists(iconv('utf-8', 'gbk', $dir)))
		{
			mkdir(iconv('utf-8', 'gbk', $dir),0777,true);
		}
		$filename =iconv('utf-8', 'gbk', $dir.$filename); //文件保存在本地的路径，注意，imgs文件夹一定要存在，否则不能写入图片
		$fp2=@fopen($filename,'a');
		@fwrite($fp2,$img,$size);
		@fclose($fp2);
		return $filename;
	}
	/**
	 * @param string $dir
	 * @desc:浏览目录下的场景全景文件夹，拼接全景图片
	 */
	public function joinPanos($dir='imgs/')
	{
		$dirs=scandir($dir);
		foreach ($dirs as $tmp)
		{
			if($tmp != '.' && $tmp !='..')
			{
				$panoDir=$dir.$tmp.'/panos/';
				$files=scandir($panoDir);
				$filename='1.png';
				$panoPng=imagecreatetruecolor(512*8, 512*4);
				$flag=0;
				foreach ($files as $file)
				{
					if($file != '.' && $file!='..')
					{
						$flag++;
						$arr=explode('.', $file);
						$name=$arr[0];
						$name=explode('_', $name);
						$i=$name[0];
						$y=$name[1];
						$name=$panoDir.$file;
						$copyImg=imagecreatefromjpeg($name);
						$dt_y=$i*512;
						$dt_x=$y*512;
						$return=imagecopy($panoPng, $copyImg, $dt_x, $dt_y, 0, 0, 512, 512);
					}
				}
// 				imagepng($panoPng,'panos/'.$filename);
				imagepng($panoPng,$dir.$tmp.'/'.$filename);
			}
		}
	}
}
$path='pano.html';
$getPanos=new getPanos();
$getPanos->downloadPanos($path);//该操作综合了一下两个方法
/* $getPanos->get_html($path);
 * $getPanos->joinPanos();
 */







