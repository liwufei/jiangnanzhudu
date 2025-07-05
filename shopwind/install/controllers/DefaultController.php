<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace install\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

use install\library\BaseInstall;

/**
 * @Id DefaultController.php 2018.10.30 $
 * @author mosir
 */

class DefaultController extends Controller
{
	/**
	 * 不启用布局
	 */
	public $layout = false;

	/**
	 * 关闭CSRF验证
	 */
	public $enableCsrfValidation = false;

	/**
	 * 当前视图
	 */
	public $view    = null;

	/**
	 * 公用参数
	 */
	public $params 	= null;

	/**
	 * 错误载体
	 */
	public $errors  = null;

	public function init()
	{
		parent::init();
		$this->params = [
			'baseUrl'		=> Basewind::baseUrl(),
			'adminUrl'		=> Basewind::adminUrl(),
			'lang' 			=> Language::find($this->id),
		];
	}

	public function actions()
	{
		return [
			'jslang' => [
				'class' => 'common\actions\JsLangAction',
				'lang'  => $this->params['lang']
			]
		];
	}

	/* 安装向导 */
	public function actionIndex()
	{
		if (Basewind::isInstall()) {
			return header('location:../');
		}
		return $this->redirect(['default/eula']);
	}

	public function actionEula()
	{
		if (Basewind::isInstall()) {
			return header('location:../');
		}

		$this->params['page'] = Page::seo(['title' => Language::get('install_eula')]);
		return $this->render('../install.eula.html', $this->params);
	}

	public function actionCheck()
	{
		if (Basewind::isInstall()) {
			return header('location:../');
		}

		// 确保是从上一页面跳转过来的
		$referrer = Yii::$app->request->referrer;
		if (empty($referrer) || stripos($referrer, Basewind::siteUrl()) === false || stripos($referrer, 'eula') === false) {
			return $this->redirect(['default/eula']);
		}

		$install = new BaseInstall();
		$check_env = $install->checkEnv(array(
			'php_version'   =>  array(
				'required'  => '>= 7.2.5',
				'checker'   => 'phpChecker',
			),
			'gd_version'   	=>  array(
				'required'  => '>= 1.0',
				'checker'   => 'gdChecker',
			),
		));
		$check_file = $install->checkFile(array(
			'../public/data',
			'../install/runtime',
			'../console/runtime',
			'../backend/runtime',
			'../frontend/home/runtime',
			'../frontend/api/runtime',
			'../frontend/mob/runtime',
		));

		$this->params['check_env'] = $check_env;
		$this->params['check_file'] = $check_file;
		$this->params['compatible'] = ($check_env['compatible'] && $check_file['compatible']) ? true : false;
		$this->params['messages'] = array_merge($check_env['msg'], $check_file['msg']);
		$this->params['hiddens'] = ['accept' => true];

		$this->params['page'] = Page::seo(['title' => Language::get('install_check')]);
		return $this->render('../install.check.html', $this->params);
	}

	public function actionConfig()
	{
		if (!Yii::$app->request->isPost) {
			if (Basewind::isInstall()) {
				return header('location:../');
			}

			// 确保是从上一页面跳转过来的
			$referrer = Yii::$app->request->referrer;
			if (empty($referrer) || stripos($referrer, Basewind::siteUrl()) === false || stripos($referrer, 'check') === false) {
				return $this->redirect(['default/eula']);
			} else Yii::$app->session->set('install_config', true);

			$this->params['hiddens'] = ['compatible' => true, 'accept' => true];
			$this->params['_head_tags'] = Resource::import('javascript/jquery.plugins/jquery.form.js');

			$this->params['page'] = Page::seo(['title' => Language::get('install_config')]);
			return $this->render('../install.config.html', $this->params);
		} else {

			// 确保是从上一页面跳转过来的
			if (Yii::$app->session->get('install_config') !== true) {
				return Message::warning(Language::get('accept'));
			}
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			if (!$post->accept) {
				return Message::warning(Language::get('accept'));
			}
			if (!$post->compatible) {
				return Message::warning(Language::get('incompatible'));
			}
			if (!preg_match('/^\w+$/', $post->db_name)) {
				return Message::warning(Language::get('db_name_error'));
			}
			$missing_items = array();
			foreach ($post as $key => $value) {
				if (empty($value)) {
					$missing_items[] = Language::get($key);
				}
			}
			if (!empty($missing_items)) {
				return Message::result($missing_items, 'missing_item');
			}

			if (!preg_match('/(?!^\\d+$)(?!^[a-zA-Z]+$)(?!^[_#@]+$).{5,}/', $post->admin_pass)) {
				return Message::warning(Language::get('admin_pass_error'));
			}

			if ($post->admin_pass != $post->confirm_pass) {
				return Message::warning(Language::get('pass_error'));
			}

			// 检查数据库连接是否正常
			$install = BaseInstall::getInstance()->build([
				'db_host' 		=> $post->db_host,
				'db_port' 		=> $post->db_port,
				'db_name'		=> $post->db_name,
				'db_user' 		=> $post->db_user,
				'db_password' 	=> $post->db_password,
				'db_prefix'		=> $post->db_prefix
			]);

			if (!$install->connect()) {
				return Message::warning($install->errors);
			}

			// 检查数据库是否存在
			if (!$install->checkDb()) {

				// 如果不存在，尝试创建该数据库
				if (!$install->createDb()) {

					// 创建不成功，显示错误
					return Message::warning($install->errors);
				}
			}
			// 如果存在数据库
			else {
				// 检查表是否存在，如果存在，提示是否可以覆盖
				if (!$install->checkTable(isset($post->force) ? $post->force : false)) {

					// 不同意强制安装，则显示错误
					return Message::warning($install->errors);
				}
			}

			return Message::display('ok');
		}
	}

	/**
	 * 该页面请允许可以多次刷新，这样可以更有效的处理建表失败后通过刷新重新创建
	 */
	public function actionBuild()
	{
		if (Basewind::isInstall()) {
			return header('location:../');
		}
		// 确保是从上一页面跳转过来的
		$referrer = Yii::$app->request->referrer;
		if (empty($referrer) || stripos($referrer, Basewind::siteUrl()) === false || stripos($referrer, 'config') === false) {
			return $this->redirect(['default/eula']);
		}

		$post = Basewind::trimAll(Yii::$app->request->post(), true);
		$this->params['hiddens'] = ArrayHelper::toArray($post);
		$this->params['messages'] = [];

		// 必须放在此方法中，放create无效
		Yii::$app->session->set('install_initdata', true);

		$this->params['page'] = Page::seo(['title' => Language::get('install_build')]);
		return $this->render('../install.build.html', $this->params);
	}

	/* 执行安装，并显示进程 */
	public function actionCreate()
	{
		// 确保是从上一页面跳转过来的
		$referrer = Yii::$app->request->referrer;
		if (empty($referrer) || stripos($referrer, Basewind::siteUrl()) === false || stripos($referrer, 'build') === false) {
			return $this->redirect(['default/eula']);
		}

		$post = Basewind::trimAll(Yii::$app->request->post(), true);

		$install = BaseInstall::getInstance()->build([
			'db_host' 		=> $post->db_host,
			'db_port' 		=> $post->db_port,
			'db_name'		=> $post->db_name,
			'db_user' 		=> $post->db_user,
			'db_password' 	=> $post->db_password,
			'db_prefix'		=> $post->db_prefix
		]);

		if (!$install->connect()) {
			$install->showProcess(Language::get('connect_db'), false, 'parent.show_warning("' . Language::get('connect_db_error') . '")');
			return false;
		}
		$install->showProcess(Language::get('connect_db'));

		// 检查数据库是否存在
		if (!$install->checkDb()) {
			// 如果不存在，尝试创建该数据库
			if (!$install->createDb()) {

				// 创建不成功，显示错误
				$install->showProcess(Language::get('create_dbname'), false, 'parent.show_warning("' . $install->errors . '")');
				return false;
			}
		}
		// 如果存在数据库
		else {
			$install->showProcess(Language::get('create_dbname'));

			// 检查表是否存在，如果存在，不在检测是否允许覆盖（因为上一步已经做了检测）
			if (!$install->checkTable(true)) {
				$install->showProcess(Language::get('create_table'), false, 'parent.show_warning("' . $install->errors . '")');
				return false;
			}
		}

		// 创建数据表
		$install->createTable();

		// 保存数据库配置
		$install->saveConfig();

		// 保存站点配置
		$install->saveSetting();

		// 创建管理账号
		$install->createAdmin($post);

		// 安装完成
		$install->finished();
	}

	public function actionInitdata()
	{
		if (!Basewind::isInstall()) {
			return $this->redirect(['default/index']);
		}

		$install = new BaseInstall();

		// 已经安装过测试数据
		if ($install->isInited()) {
			return Message::warning(Language::get('initdata_locked'));
		}

		// 确保开放了安装测试数据权限
		if (Yii::$app->session->get('install_initdata') !== true) {
			return Message::warning(Language::get('allow_initdata'));
		}

		if (!Yii::$app->request->isPost) {
			$this->params['page'] = Page::seo(['title' => Language::get('install_initdata')]);
			return $this->render('../install.initdata.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if (empty($post->seller) || empty($post->buyer) || ($post->seller == $post->buyer)) {
				return Message::warning(Language::get('seller_buyer_empty'), ['default/initdata']);
			}

			if (UserModel::find()->where(['username' => $post->seller])->exists()) {
				return Message::warning(Language::get('seller_name_exists'), ['default/initdata']);
			}
			if (UserModel::find()->where(['username' => $post->buyer])->exists()) {
				return Message::warning(Language::get('buyer_name_exists'), ['default/initdata']);
			}

			// 注册用户
			if (!($seller_id = $install->createUser($post->seller, '123456'))) {
				return Message::warning(Language::get('seller_create_fail'), ['default/initdata']);
			}
			if (!($buyer_id = $install->createUser($post->buyer, '123456'))) {
				return Message::warning(Language::get('buyer_create_fail'), ['default/initdata']);
			}

			// 复制文件
			$install->copyFiles();

			// 创建记录
			$install->initData($buyer_id, $seller_id);

			// 安装结束，锁定程序
			$install->initend();

			return $this->redirect(['default/success']);
		}
	}

	/**
	 * 安装成功页面
	 */
	public function actionSuccess()
	{
		if (!Basewind::isInstall()) {
			return $this->redirect(['default/index']);
		}

		$this->params['page'] = Page::seo(['title' => Language::get('install_finished')]);
		return $this->render('../install.success.html', $this->params);
	}
}
