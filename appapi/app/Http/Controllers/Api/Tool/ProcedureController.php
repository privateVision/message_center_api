<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\Procedures;
use App\Model\ZyGame;
use App\Model\SignAPK;

class ProcedureController extends Controller
{
    public function QueryAction() {
        $rid = $this->parameter->tough('rid');
        $pname = $this->parameter->get('pname');

        $ps = SignAPK::where('rid', $rid)->groupBy('pid')->pluck('pid');

        $procedure = Procedures::select('pid', 'pname', 'gameCenterId')->whereIn('pid', $ps);

        if($pname) {
            $procedure = $procedure->where('pname', 'like', "%{$pname}%");
        }

        $result = $procedure->orderBy('pname', 'asc')->get();

        $data = [];
        foreach($result as $k => $v) {
            $data[$k]['pid'] = $v->pid;
            $data[$k]['pname'] = $v->pname;
            $data[$k]['icon'] = '';
            $data[$k]['game_id'] = '';

            $game = ZyGame::from_cache($v->gameCenterId);
            if($game) {
                $data[$k]['icon'] = $game->cover;
                $data[$k]['game_id'] = $game->id;
            }
        }

        return array_values($data);
    }
}