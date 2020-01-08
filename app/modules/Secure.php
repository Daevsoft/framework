<?php 
/**
* Secure Class
* Author by Muhamad Deva Arofi
*/
class Secure
{
	private $__vals  = array();
	private $__abj = "aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ1234567890";
	function __construct()
	{
		$this->__vals = explode(",", "HIa,2Df,aem,AfF,b5C,f2F,1vJ,FVA,ZCx,
			BM,cBn,CIo,dJu,DHu,k4f,5Ue,KHu,gJb,Gvf,h3e,xer,XTr,nHg,8Qe,NEd,oFe,
			9B5,OYu,pBu,yKf,Y4r,z2g,6Gd,lA3,TtM,uvG,Ujx,vzD,VfF,wgG,Ldf,7vb,mfs,
			MGf,Prf,ity,IJd,30o,jip,RoI,sdU,0Hw,SDx,4xZ,JfJ,tSd,WbM,q4R,QOk,rmK,elD,Ert");
	}
	public function encrypt($__val)
	{
		$__enc = $this->__abj;
		$__encrypt = array();
		for ($i=0; $i < strlen($__val); $i++) {
			$__encrypt[$i] = $this->__vals[strpos($this->__abj, $__val[$i])];
		}
		$__e = "";
		foreach (array_reverse($__encrypt) as $val) {
			$__e .= substr(md5($val), 3, 6);
		}
		return md5($__e);
	}
	public function bforce_init()
	{
		$_captcha = "";
		for ($i=0; $i < 4; $i++) { 
			$_captcha .= $this->__vals[rand(1, count($this->__vals))];
		}
		$_SESSION['tokenRequest'] = $this->encrypt($_captcha);
	}
	public function bforce_post($__inputName)
	{
		$tokenRequest = BackEnd::_get('_x_');// token
		if ($tokenRequest == $_SESSION['tokenRequest']) {
			return BackEnd::_post($__inputName);
		}else{
			die("Request Anonymous found !");
		}
	}
}