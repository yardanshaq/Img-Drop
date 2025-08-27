<?php
session_start();

// ===== Simple Cloud-Style Image Host =====
$title1 = 'Img Drop';
$title2 = 'Img Drop | Cloud Image';
$github = 'https://github.com/yardanshaq';
$filedir = 'up';
$maxsize = 10 * 1024 * 1024; // 10 MB
$allowedExts = ['png','jpg','jpeg','gif','webp'];
$allowedMime = ['image/png','image/jpeg','image/pjpeg','image/gif','image/webp'];

if (!is_dir($filedir)) {
    @mkdir($filedir, 0755, true);
}

function clean_filename($name){
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return preg_replace('/_{2,}/','_',$name);
}

function random_filename($ext){
    try { return bin2hex(random_bytes(6)).'.'.$ext; }
    catch(Exception $e){ return uniqid().'.'.$ext; }
}

function base_domain(){
    return $_SERVER['HTTP_HOST']; // domain tanpa http/https
}

// Handle upload
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['images'])){
    $files = $_FILES['images'];
    $count = is_array($files['name'])?count($files['name']):0;
    $messages = [];

    if(!isset($_SESSION['gallery'])) $_SESSION['gallery'] = [];

    for($i=0;$i<$count;$i++){
        if($files['error'][$i]!==UPLOAD_ERR_OK){
            $messages[]=['type'=>'error','text'=>'Please select a file '.htmlspecialchars($files['name'][$i])];
            continue;
        }
        if($files['size'][$i]>$maxsize){
            $messages[]=['type'=>'error','text'=>'File too large: '.htmlspecialchars($files['name'][$i])];
            continue;
        }

        $tmp = $files['tmp_name'][$i];
        $name = clean_filename($files['name'][$i]);
        $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));

        if(!in_array($ext,$allowedExts)){
            $messages[]=['type'=>'error','text'=>'Invalid extension: '.htmlspecialchars($name)];
            continue;
        }

        $check=@getimagesize($tmp);
        if($check===false || !in_array($check['mime'],$allowedMime)){
            $messages[]=['type'=>'error','text'=>'Invalid file type: '.htmlspecialchars($name)];
            continue;
        }

        $randomName=random_filename($ext);
        $target=$filedir.'/'.$randomName;

        if(move_uploaded_file($tmp,$target)){
            $messages[]=['type'=>'success','text'=>'Uploaded '.$randomName,'url'=>$randomName];
            $_SESSION['gallery'][] = $randomName;
        } else {
            $messages[]=['type'=>'error','text'=>'Failed to save '.htmlspecialchars($name)];
        }
    }

    $_SESSION['messages']=$messages;
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Ambil pesan dari session
$messages = $_SESSION['messages'] ?? [];
unset($_SESSION['messages']);

// Ambil galeri private
$gallery = $_SESSION['gallery'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo htmlspecialchars($title2); ?></title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.tailwindcss.com"></script>
<meta name="color-scheme" content="light dark">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='currentColor'%3E%3Cpath d='M20 17.5A4.5 4.5 0 0 0 15.5 13H14a5 5 0 0 0-9.58 1.5'/%3E%3Cpath d='M16 16l-3-3-4 5h12'/%3E%3C/svg%3E" />
</style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-100">
<div class="max-w-6xl mx-auto px-4 py-8">

<!-- Header -->
<header class="flex items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <div class="h-10 w-10 rounded-2xl bg-white/70 dark:bg-white/10 backdrop-blur border border-white/40 dark:border-white/10 flex items-center justify-center shadow">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M20 17.5A4.5 4.5 0 0 0 15.5 13H14a5 5 0 0 0-9.58 1.5" />
        <path d="M16 16l-3-3-4 5h12" />
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight"><?php echo htmlspecialchars($title1); ?></h1>
      <p class="text-sm text-slate-500 dark:text-slate-400">Minimal cloud-style image hosting</p>
    </div>
  </div>
  <a href="<?php echo htmlspecialchars($github); ?>" class="text-sm opacity-70 hover:opacity-100 underline">GitHub</a>
</header>

<!-- Messages -->
<?php if(!empty($messages)): ?>
<div class="mt-6 space-y-2">
<?php foreach($messages as $m): ?>
<div class="rounded-xl px-4 py-3 border shadow-sm <?php echo $m['type']==='success'?'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800':'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800'; ?>">
  <p class="text-sm">
    <?php echo htmlspecialchars($m['text']); ?>
    <?php if(!empty($m['url'])): ?>
    — <a class="underline break-all" href="https://<?php echo htmlspecialchars(base_domain().'/'.$m['url']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(base_domain().'/'.$m['url']); ?></a>
    <?php endif; ?>
  </p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Uploader -->
<section class="mt-8">
<div class="rounded-2xl border border-white/60 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6 shadow-lg">
<form id="uploadForm" class="space-y-4" action="" method="post" enctype="multipart/form-data">

  <div id="previewContainer" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4 mb-4"></div>

  <div id="dropzone" class="group relative rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 p-8 flex flex-col items-center justify-center text-center transition hover:border-slate-400 dark:hover:border-slate-600">
    <input class="absolute inset-0 opacity-0 cursor-pointer" type="file" name="images[]" id="fileInput" accept="image/*" multiple>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-3 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path d="M20 17.5A4.5 4.5 0 0 0 15.5 13H14a5 5 0 0 0-9.58 1.5" />
      <path d="M16 16l-3-3-4 5h12" />
    </svg>
    <p class="text-sm">Drag & drop images here or <span class="underline">browse</span></p>
    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Max <?php echo floor($maxsize/1024/1024); ?>MB, allowed: <?php echo implode(', ', $allowedExts); ?></p>
  </div>

  <button class="rounded-xl px-4 py-2 bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow hover:shadow-md active:scale-[.99]" type="submit">Upload</button>
</form>
</div>
</section>

<!-- Gallery -->
<section class="mt-10">
<div class="flex items-center justify-between mb-3">
  <h2 class="text-lg font-semibold">Recent uploads</h2>
  <span class="text-xs opacity-70"><?php echo count($gallery); ?> image(s)</span>
</div>
<?php if(empty($gallery)): ?>
<p class="text-sm text-slate-500 dark:text-slate-400">No images yet. Upload something!</p>
<?php else: ?>
<div id="galleryContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
<?php foreach($gallery as $key=>$file): 
    $fileUrl = base_domain().'/'.basename($file);
?>
<div class="group relative rounded-2xl overflow-hidden border border-white/60 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur shadow hover:shadow-lg gallery-item">
  <img src="<?php echo htmlspecialchars($file); ?>" alt="" class="w-full h-40 object-cover">
  
  <div class="gallery-overlay absolute inset-0 flex flex-col justify-center items-center gap-2 rounded-2xl">
    <a href="https://<?php echo $fileUrl; ?>" target="_blank" class="px-2 py-1 bg-green-600 rounded text-xs text-white z-20 pointer-events-auto">Open Image</a>
    <button onclick="navigator.clipboard.writeText('https://<?php echo $fileUrl; ?>')" class="px-2 py-1 bg-blue-600 rounded text-xs text-white z-20 pointer-events-auto">Copy URL</button>
  </div>

  <button class="absolute top-1 right-1 z-30 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs" onclick="deleteGallery(<?php echo $key; ?>)">✕</button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<footer class="mt-12 text-xs text-center opacity-70">
<p>&copy; <?php echo date('Y'); ?> • <?php echo htmlspecialchars($title1); ?></p>
</footer>

</div>

<script>
const fileInput=document.getElementById('fileInput');
const previewContainer=document.getElementById('previewContainer');
let filesArray=[];

fileInput.addEventListener('change', ()=>{ Array.from(fileInput.files).forEach(f=>{ if(!f.type.startsWith('image/')) return; filesArray.push(f); }); renderPreviews(); });

function renderPreviews(){
  previewContainer.innerHTML='';
  filesArray.forEach((file,index)=>{
    const reader=new FileReader();
    reader.onload=(e)=>{
      const div=document.createElement('div');
      div.className='relative w-full pb-[100%] rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 draggable';
      div.setAttribute('draggable','true');
      const img=document.createElement('img');
      img.src=e.target.result;
      img.className='absolute top-0 left-0 w-full h-full object-cover';
      div.appendChild(img);

      const btnDelete=document.createElement('button');
      btnDelete.innerHTML='✕';
      btnDelete.className='absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs z-20';
      btnDelete.addEventListener('click',()=>{ filesArray.splice(index,1); renderPreviews(); });
      div.appendChild(btnDelete);

      previewContainer.appendChild(div);
    }
    reader.readAsDataURL(file);
  });
}

document.getElementById('uploadForm').addEventListener('submit',(e)=>{
  const dt=new DataTransfer();
  filesArray.forEach(f=>dt.items.add(f));
  fileInput.files=dt.files;
});

// Delete from gallery
function deleteGallery(idx){ fetch('?delete='+idx).then(()=>location.reload()); }

// Drag-drop reorder
let dragSrcEl=null;
function handleDragStart(e){ dragSrcEl=this; e.dataTransfer.effectAllowed='move'; }
function handleDragOver(e){ e.preventDefault(); e.dataTransfer.dropEffect='move'; }
function handleDrop(e){ e.preventDefault(); if(dragSrcEl!==this){ const nodes=[...previewContainer.children]; const srcIdx=nodes.indexOf(dragSrcEl); const tgtIdx=nodes.indexOf(this); previewContainer.insertBefore(dragSrcEl,(srcIdx<tgtIdx?this.nextSibling:this)); } }
function addDnDListeners(){ [...previewContainer.children].forEach(c=>{ c.addEventListener('dragstart',handleDragStart); c.addEventListener('dragover',handleDragOver); c.addEventListener('drop',handleDrop); }); }
setInterval(addDnDListeners,500);

// Toggle overlay on mobile
document.querySelectorAll('.gallery-item').forEach(item=>{
  item.addEventListener('click', e=>{
    if(e.target.tagName.toLowerCase()==='button' || e.target.tagName.toLowerCase()==='a') return;
    item.classList.toggle('show');
  });
});

// Handle delete query
<?php
if(isset($_GET['delete']) && isset($_SESSION['gallery'][$_GET['delete']])) {
  $file=$_SESSION['gallery'][$_GET['delete']];
  $filePath=$filedir.'/'.$file;
  if(file_exists($filePath)) unlink($filePath);
  unset($_SESSION['gallery'][$_GET['delete']]);
  $_SESSION['gallery']=array_values($_SESSION['gallery']);
  header('Location: '.$_SERVER['PHP_SELF']);
  exit;
}
?>
</script>
</body>
</html>