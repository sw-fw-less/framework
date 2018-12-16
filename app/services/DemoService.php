<?php

namespace App\services;

use App\components\Helper;
use App\components\Response;
use App\facades\File;
use App\models\Member;
use App\models\Test;
use Cake\Validation\Validator;
use Swlib\SaberGM;

class DemoService extends BaseService
{
    public function redis()
    {
        $params = $this->getRequest()->all();

        //Param Validation
        $errors = (new Validator())->requirePresence('key')
            ->lengthBetween('key', [1, 10])
            ->add('key', 'string', [
                'rule' => [\App\components\Validator::class, 'string'],
                'message' => 'key is not a string'
            ])->errors($params);
        if (count($errors) > 0) {
            return Response::json(['code' => 1, 'msg' => Helper::jsonEncode($errors), 'data' => []]);
        }

//        unlink('redis://' . $params['key']);
//        file_put_contents('redis://' . $params['key'], 'value2');

        $result = file_get_contents('redis://' . $params['key']);

        file_put_contents('log://info', 'test error');

        return Response::json(['code' => 0, 'msg' => 'ok', 'data' => $result]);
    }

    public function mysql()
    {
        $queryResult = Member::select()
            ->cols(['*'])
            ->where('id in (111426517, 111426518)')
            ->limit(2)
            ->first()
            ->toArray();

        return Response::json($queryResult);
    }

    public function http()
    {
        $res = SaberGM::get('http://news.baidu.com/widget?ajax=json&id=ad');

        return Response::json($res->getBody());
    }

    public function es()
    {
        $models = Test::query()->filterTerm('foo', 'bar')->search();

        $result = [];
        foreach ($models as $model) {
            $result[] = $model->toArray();
        }

        return Response::json($result);
    }

    public function file()
    {
        $filesystem = File::prepare();
        if ($filesystem->has('test.txt')) {
            $filesystem->delete('test.txt');
        }
        $filesystem->write('test.txt', 'test');
        return Response::output('ok');
    }
}
