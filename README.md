# bitrix-BXIblockActions

## Пример исопользования

//вывод ошибок и успешно выполненных операций
BXIblockActions::getInstance()->output(true);

//Создание нового типа инфоблоков
BXIblockActions::getInstance()->addIblockType()->code('Requests')->name('Заявки')->elementName('element')->sections()->sort(500)->add();

//Создание инфоблока
$reqIblockId = BXIblockActions::getInstance()->addIblock()->name('Заявка')->code('Request')->type('Requests')->add();

//Создание свойства для инфоблока
BXIblockActions::getInstance()->addProperty()->string()->iblock($reqIblockId)->name('Тип заявки')->code('type')->add();
