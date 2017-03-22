<?php
namespace App\Jobs;

use App\Redis;
use App\Model\UserRole;
use App\Model\ProceduresZone;

class AsyncExecute extends Job
{
    protected $method;

    protected $arguments;

    public function __construct($method, $arguments)
    {
        $this->method = $method;
        $this->arguments = $arguments;
    }

    public function handle()
    {
        $method = $this->method;
        $arguments = $this->arguments;
        $this->$method(...$arguments);
    }

    public function report_role($ucid, $pid, $user_sub_id, $zone_id, $zone_name, $role_id, $role_name, $role_level) {
        // todo: 非线程安全，无法保证顺序执行
        $user_role_uuid = joinkey($pid, $ucid, $user_sub_id, $zone_id, $role_id);
        $user_role = UserRole::tableSlice($pid)->from_cache($user_role_uuid);
        if(!$user_role) {
            $user_role = UserRole::tableSlice($pid);
            $user_role->id = $user_role_uuid;
            $user_role->ucid = $ucid;
            $user_role->pid = $pid;
            $user_role->user_sub_id = $user_sub_id;
            $user_role->intRoleLevel = $role_level;
            $user_role->zoneId = $zone_id;
            $user_role->roleId = $role_id;
        }

        $user_role->zoneName = $zone_name;
        $user_role->roleName = $role_name;
        $user_role->roleLevel = $role_level;
        $user_role->save();

        $procedures_zone_uuid = joinkey($pid, $zone_id);
        Redis::mutex_lock('lock_pz_' . $procedures_zone_uuid, function() use($procedures_zone_uuid, $pid, $zone_id, $zone_name) {
            $procedures_zone = ProceduresZone::from_cache_uuid($procedures_zone_uuid);
            if(!$procedures_zone) {
                $procedures_zone = new ProceduresZone;
                $procedures_zone->uuid = $procedures_zone_uuid;
                $procedures_zone->pid = $pid;
                $procedures_zone->zone_id = $zone_id;
                $procedures_zone->zone_name = $zone_name;
                $procedures_zone->save();
            }

            $procedures_zone->zone_name = $zone_name;
            $procedures_zone->save();
        });
    }
}