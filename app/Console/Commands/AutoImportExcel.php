<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportController;

class AutoImportExcel extends Command
{
    protected $signature = 'import:auto';
    protected $description = 'واردات خودکار فایل اکسل از مسیر تنظیم‌شده در .env';

    public function handle()
    {
        $this->info('شروع واردات خودکار...');
        
        $controller = new ImportController();
        $result = $controller->importFromPath(); // متدی که قبلاً نوشتیم
        
        $this->info('واردات انجام شد.');
        return 0;
    }
}