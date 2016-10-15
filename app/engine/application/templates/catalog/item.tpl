<?

$templates = new League\Plates\Engine('../templates');

echo $templates->render('list_items', ['name' => 'Jonathan']);