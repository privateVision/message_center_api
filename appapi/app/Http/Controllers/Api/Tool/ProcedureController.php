<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\Procedures;
use App\Model\ZyGame;

class ProcedureController extends Controller
{
    public function QueryAction() {
        $pname = $this->parameter->get('pname');

        $procedure = Procedures::select('pid', 'pname', 'gameCenterId');

        if($pname) {
            $procedure = $procedure->where('pname', 'like', "%{$pname}%");
        }

        $result = $procedure->orderBy('pname', 'asc')->get();

        $data = [];
        foreach($result as $k => $v) {
            $data[$k]['pid'] = $v->pid;
            $data[$k]['pname'] = $v->pname;
            $data[$k]['icon'] = '';

            $game = ZyGame::from_cache($v->gameCenterId);
            if($game) {
                $data[$k]['icon'] = $game->cover;
            }
        }

        return array_values($data);
    }
}