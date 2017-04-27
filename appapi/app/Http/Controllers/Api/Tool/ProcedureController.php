<?php
namespace App\Http\Controllers\Api\Tool;

use App\Exceptions\ApiException;
use App\Model\Procedures;

class ProcedureController extends Controller
{
    public function QueryAction() {
        $pname = $this->parameter->tough('pname');

        $isWhere = false;
        $procedure = Procedures::select('pid', 'pname');

        if($pname) {
            $isWhere = true;
            $procedure = $procedure->where('pname', 'like', "%{$pname}%");
        }

        if(!$isWhere) return [];

        return $procedure->orderBy('pname', 'asc')->get();
    }
}