<?php

namespace App\Http\Controllers;

use App\SystemPlatform;
use App\SystemVersion;
use Illuminate\Http\Request;

class SystemVersionController extends Controller
{
    public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length') ? $request->input('length') : 10;
        $search = $request->input('search')['value'];

        $active = $request->input('active');
        $platform = $request->input('platform');
        $os_name = $request->input('os_name');

        $recordsTotal = SystemVersion::count();

        $query = SystemVersion::select('system_versions.*', 'system_platforms.platform', 'system_platforms.os_name')
            ->leftJoin('system_platforms', 'system_platforms.id', '=', 'system_versions.platform_id')
            ->when($search, function ($query) use ($search) {
                return $query->where('os_name', 'like', "%$search%");
            })
            ->when($active, function ($query) use ($active) {
                return $query->where('active', $active);
            })
            ->when($platform, function ($query) use ($platform) {
                return $query->where('platform', $platform);
            })
            ->when($os_name, function ($query) use ($os_name) {
                return $query->where('os_name', $os_name);
            })
            ->orderBy('system_versions.platform_id', 'ASC')
            ->orderBy('system_versions.release_date', 'DESC')
            ->offset($start)
            ->limit($length);

        $versions = $query->get();

        $recordsFilterTotal = $query->count();

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $versions,
        ]);
    }

    /**
     * Update version status (active 1=>0, 0=>1).
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        $id = $request->input('id');

        $id_arr = explode("_", $id);
        if ($id_arr[0] != "versions") {
            return response()->json([
                "success" => false,
            ]);
        }
        $version_id = intval($id_arr[1]);
        $item = SystemVersion::where('id', '=', $version_id)->get()->first();
        $platform_id = $item->platform_id;

        SystemVersion::where('platform_id', '=', $platform_id)->where('active', '=', 1)->update(['active' => 0]);
        $item->active = 1;
        $item->save();
        return response()->json([
            "version" => $item,
            "success" => true,
        ]);
    }

    /**
     * Display a listing of platforms.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPlatforms()
    {
        return response()->json([
            "platforms" => SystemPlatform::orderBy('platform', 'ASC')->orderBy('os_name', 'ASC')->get(),
            "success" => true,
        ]);
    }

    /**
     * Create a new platform.
     *
     * @return \Illuminate\Http\Response
     */
    public function createPlatform(Request $request)
    {
        $this->validate($request, [
            'os_name' => 'required',
        ]);
        $input = $request->only(['platform', 'os_name']);
        SystemPlatform::create($input);
        return response('', 204);
    }

    /**
     * Update a existing platform.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePlatform(Request $request, $id)
    {
        $this->validate($request, [
            'os_name' => 'required',
        ]);
        $input = $request->only(['platform', 'os_name']);
        SystemPlatform::where('id', '=', $id)->update($input);
        return response('', 204);
    }

    /**
     * Delete a specific platform.
     *
     * @return \Illuminate\Http\Response
     */
    public function deletePlatform(Request $request)
    {
        $id = $request->input('platform_id');
        SystemPlatform::where('id', $id)->delete();

        return response('', 204);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'app_name' => 'required',
            'file_name' => 'required',
            'alpha' => 'required',
            'beta' => 'required',
            'stable' => 'required',
            'release_date' => 'required',
        ]);
        $input = $request->only(['platform_id', 'app_name', 'file_name', 'version_number', 'changes', 'alpha', 'beta', 'stable', 'release_date', 'active']);

        if ($input['active'] == 1) {
            $rows = SystemVersion::where('platform_id', '=', $input['platform_id'])->where('active', '=', 1)->get();
            foreach ($rows as $row) {
                $row->active = 0;
                $row->save();
            }
        }
        SystemVersion::create($input);
        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item_for_del = SystemVersion::where('id', $id)->first();
        $active = $item_for_del->active;
        $platform_id = $item_for_del->platform_id;
        if (!is_null($item_for_del)) {
            $item_for_del->delete();
        }
        if ($active === 1) {
            $item_for_update = SystemVersion::where('platform_id', '=', $platform_id)->orderBy('release_date', 'DESC')->first();
            $item_for_update->active = 1;
            $item_for_update->save();
        }
        return response('', 204);
    }

    /**
     * Update the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'app_name' => 'required',
            'file_name' => 'required',
            'alpha' => 'required',
            'beta' => 'required',
            'stable' => 'required',
            'release_date' => 'required',
        ]);
        $input = $request->only(['id', 'platform_id', 'app_name', 'file_name', 'version_number', 'changes', 'alpha', 'beta', 'stable', 'release_date', 'active']);
        $platform_id = $input['platform_id'];
        $active = $input['active'];
        $item = SystemVersion::where('id', '=', $id)->get()->first();
        $prev_active = $item->active;
        $prev_platform_id = $item->platform_id;

        SystemVersion::where('id', '=', $id)->update($input);
        return response('', 204);
    }
}
