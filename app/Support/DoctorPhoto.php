<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorPhoto
{
    private const EXT = ['jpg', 'jpeg', 'png', 'webp'];

    private static function disk() { return Storage::disk('public'); }
    private static function base(string $name): string { return 'doctors/'.Str::slug($name); }

  public static function url(string $name): ?string
{
    foreach (self::EXT as $e) {
        $p = self::base($name).".$e";
        if (self::disk()->exists($p)) {
            return asset('storage/'.$p).'?v='.self::disk()->lastModified($p);
        }
    }
    return null;
}

    public static function store(string $name, UploadedFile $file): void
    {
        self::forget($name);
        $file->storeAs('doctors', Str::slug($name).'.'.$file->extension(), 'public');
    }

    public static function forget(string $name): void
    {
        foreach (self::EXT as $e) { self::disk()->delete(self::base($name).".$e"); }
    }

 
    public static function rename(string $old, string $new): void
    {
        if (Str::slug($old) === Str::slug($new)) return;
        foreach (self::EXT as $e) {
            if (self::disk()->exists(self::base($old).".$e")) {
                self::disk()->move(self::base($old).".$e", self::base($new).".$e");
            }
        }
    }
}