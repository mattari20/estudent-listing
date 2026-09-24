<?php
namespace App\Services;
use App\Models\Geography;
use Illuminate\Support\Facades\DB;
class PakistanGeographySeedService {
 public const TOP_LEVEL = [
  ['name'=>'Pakistan','slug'=>'pakistan','type'=>'country','code'=>'PK'],
  ['name'=>'Punjab','slug'=>'punjab','type'=>'province','code'=>'PK-PB'],
  ['name'=>'Sindh','slug'=>'sindh','type'=>'province','code'=>'PK-SD'],
  ['name'=>'Khyber Pakhtunkhwa','slug'=>'khyber-pakhtunkhwa','type'=>'province','code'=>'PK-KP'],
  ['name'=>'Balochistan','slug'=>'balochistan','type'=>'province','code'=>'PK-BA'],
  ['name'=>'Islamabad Capital Territory','slug'=>'islamabad-capital-territory','type'=>'territory','code'=>'PK-IS'],
  ['name'=>'Gilgit-Baltistan','slug'=>'gilgit-baltistan','type'=>'territory','code'=>'PK-GB'],
  ['name'=>'Azad Jammu and Kashmir','slug'=>'azad-jammu-and-kashmir','type'=>'territory','code'=>'PK-AJ'],
 ];
 public function seedTopLevel(): int {
  return DB::transaction(function(){ $country=Geography::updateOrCreate(['slug'=>'pakistan','type'=>'country'],array_merge(self::TOP_LEVEL[0],['parent_id'=>null,'status'=>'active'])); $count=1; foreach(array_slice(self::TOP_LEVEL,1) as $row){Geography::updateOrCreate(['slug'=>$row['slug'],'type'=>$row['type']],array_merge($row,['parent_id'=>$country->id,'status'=>'active']));$count++;} return $count; });
 }
}