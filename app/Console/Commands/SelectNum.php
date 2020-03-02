<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SelectNum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'select:num';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '选号';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        var_dump(count(json_decode(Cache::get("list-188"), true)));

        $noticeUrl = "https://oapi.dingtalk.com/robot/send?access_token=d4d17f2fb8f0e6f6798cbc744f39157b38b39177c5416c5f4641786091269b57";

        $box = ['135', '136', '137', '138', '139', '158', '187', '188'];
        $all = [];
        foreach ($box as $pre) {
            for ($i = 1; $i <= 10; $i++) {
                $res       = $this->_get($pre, $i);
                $all[$pre] = array_merge($res, $all[$pre] ?? []);
                if (count($res) < 10) {
                    asort($all[$pre]);
                    if (!Cache::has("list-" . $pre) || count(json_decode(Cache::get("list-" . $pre),
                            true)) != count($all[$pre])) {
                        Cache::put("list-" . $pre, json_encode($all[$pre], JSON_UNESCAPED_UNICODE), 60 * 60 * 6);

                        $requestData = [
                            'msgtype' => 'text',
                            'text'    => [
                                "content" => "时间：" . date("Y-m-d H:i:s",
                                        time()) . "\n " . $pre . "开列表：" . count($all[$pre]) . "\n" . implode(",\n",
                                        $all[$pre])
                            ],
                            'at'      => [
                                'atMobiles' => [13520221200],
                                'isAtAll'   => false,
                            ]
                        ];

                        $this->request_by_curl($noticeUrl, json_encode($requestData));
                    }
                    break;
                }
            }
        }
//        var_dump($all);

//        var_dump(json_decode(Cache::get("list-188")));
//        file_put_contents('/Users/wenba/www/nmg/number.txt', "=====" . date("Y-m-d H:i:s") . "=====");
        file_put_contents('/Users/wenba/www/nmg/number.txt',
            "=====" . date("Y-m-d H:i:s") . "=====\n" . json_encode($all, JSON_UNESCAPED_UNICODE), FILE_APPEND);
    }

    public function _get($pre, $num)
    {
        $array = file_get_contents('http://eshop.nm135.cn/eshop/wap/onlineCardNew/searchnumber.do?cityId=71000130&segment=' . $pre . '&pageNo=' . $num);
//        $array = '
//
//<ul class="number-list">
//        <li class="curr">13848117520</li>
//        <li >13847179520</li>
//        <li >13848129520</li>
//        <li >13848123520</li>
//        <li >13848168520</li>
//        <li >13848515520</li>
//        <li >13848158520</li>
//        <li >13847162520</li>
//        <li >13848120520</li>
//        <li >13848142520</li>
//
//</ul>
//
//';
        preg_match_all('/<li *>(.*?)<\/li>/', str_replace('class="curr"', '', $array), $new);
        return $new[1];
    }
}
