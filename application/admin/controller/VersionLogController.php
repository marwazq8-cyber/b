<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-11-20
     * Time: 14:18
     */

    namespace app\admin\controller;


    use cmf\controller\AdminBaseController;

    class VersionLogController extends AdminBaseController
    {

        public function index()
        {
            $p = $this->request->param('page');
            if (empty($p) and $this->request->param('status')<0 and !$this->request->param('user_id') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
                session("admin_call", null);
                $data['status'] = '-1';
                session("AutoTalking", $data);
            } else if (empty($p)) {

                $data['status'] = $this->request->param('status') >= '0' ? $this->request->param('status') :'-1';
                $data['user_id'] = $this->request->param('user_id') ?$this->request->param('user_id') :'';
                $data['start_time'] = $this->request->param('start_time') ?$this->request->param('start_time') :'';
                $data['end_time'] = $this->request->param('end_time') ?$this->request->param('end_time') :'';
                session("AutoTalking", $data);
            }

            $status = session("AutoTalking.status");
            $user_id = session("AutoTalking.user_id");
            $start_time = session("AutoTalking.start_time");
            $end_time = session("AutoTalking.end_time");

            $where= 'c.id >0 and c.type = 2';

            $where .= $status >='0'? ' and c.status='.$status : '';
            $where .= $user_id ? ' and c.user_id='.$user_id : '';
            $where .= $start_time ? ' and c.create_time >='.strtotime($start_time) : '';
            $where .= $end_time ? ' and c.create_time < '.strtotime($end_time) : '';

            $talking = db('version_log');

            $list = $talking
                //->where($where)
                ->order("create_time DESC")
                ->paginate(20, false, ['query' => request()->param()]);

            $lists = $list->toArray();
            // 获取分页显示
            $page = $list->render();
            $this->assign('list', $lists['data']);
            $this->assign('page', $page);
            $this->assign('request', session("AutoTalking"));
            // 渲染模板输出
            return $this->fetch();
        }

        public function add(){
            $id = input('id');
            if($id){
                $data = db('version_log')->find($id);
            }else{
                $data['type'] = 1;
                $data['is_update'] = 1;
                $data['is_release'] = 1;
            }

            $this->assign('data', $data);
            return $this->fetch();
        }

        public function addPost(){
            $param = $this->request->param();
            //print_r($param);exit;
            $id = $param['id'];
            $data = $param['post'];
            //$data['url'] = $data['url'];
            $data['create_time'] = time();
            //dump($data);die();
            if ($id) {
                $result = db("version_log")->where("id=$id")->update($data);
            } else {
                $result = db("version_log")->insertGetId($data);
                $id = $result;
            }
            if ($result) {
                if($data['is_release']==1){
                    db("version_log")
                        ->where('type = '.$data['type'].' and id != '.$id)
                        ->update(['is_release'=>0]);
                }
                if ($data['type'] == 1){
                    // 如果是ios类型处理，需要更新plist文件；前端跳转plist执行下载
                    if($data['url']){
                        $this->save_ios_download($data);
                    }
                }
                $this->success(lang('EDIT_SUCCESS'), url('VersionLog/index'));
            } else {
                $this->error(lang('EDIT_FAILED'));
            }
        }
        public function save_ios_download($data){
            $url = $data['url'];
            $package_name = $data['package_name']; // ios包名

            $config = load_cache('config');
            $name = $config['system_name']; //  com.vivovoicechat.app
            $html='<?xml version="1.0" encoding="UTF-8"?>
                      <plist version="1.0"><dict>
                          <key>items</key>
                          <array>
                            <dict>
                              <key>assets</key>
                              <array>
                                <dict>
                                  <key>kind</key>
                                  <string>software-package</string>
                                  <key>url</key>
                                  <string><![CDATA['.$url.']]></string>
                                </dict>
                                <dict>
                                  <key>kind</key>
                                  <string>display-image</string>
                                  <key>needs-shine</key>
                                  <integer>0</integer>
                                  <key>url</key>
                                  <string><![CDATA[]]></string>
                                </dict>
                                <dict>
                                  <key>kind</key>
                                  <string>full-size-image</string>
                                  <key>needs-shine</key>
                                  <true/>
                                  <key>url</key>
                                  <string><![CDATA[]]></string>
                                </dict>
                              </array>
                              <key>metadata</key>
                              <dict>
                                <key>bundle-identifier</key>
                                <string>'.$package_name.'</string>
                                <key>bundle-version</key>
                                <string><![CDATA[1.1.0]]></string>
                                <key>kind</key>
                                <string>software</string>
                                <key>title</key>
                                <string><![CDATA['.$name.']]></string>
                              </dict>
                            </dict>
                          </array>
                        </dict></plist>
                    ';
            $url = DOCUMENT_ROOT.'/ios.plist';
            $file_status = file_put_contents($url,$html);
            return $file_status ? true : false;
        }
        //删除
        public function del()
        {
            $param = request()->param();
            $result = db("version_log")->where("id=" . $param['id'])->delete();
            return $result ? '1' : '0';
            exit;
        }
    }
