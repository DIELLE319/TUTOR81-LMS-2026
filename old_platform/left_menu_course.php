<?php
require_once 'lib/check_session.php';
require_once 'lib/class_course.php';
require_once 'lib/sanitize.php';

$course_obj = new iWDCourse();
$learning_list = $course_obj->getListLearning();
$course_list = $course_obj->getList();

$id = isset($_GET['id']) ? filter_input(INPUT_GET,'id', FILTER_SANITIZE_NUMBER_INT) : 0;

if ($_SESSION['user_role'] == 1 ){
    require_once 'lib/class_company.php';
    $comp_obj = new iWDCompany();
    $companies_tutor = $comp_obj->getCompanyByTutorCompany($_SESSION['user_company_id']);
}
?>

<script>
	function filterCourse(filter){
		if (filter == 'all'){
			$('.link_course').show();
			$('#test').prop('checked', true);
			$('#demo').prop('checked', true);
			$('#customs').prop('checked', true);
		} else {
			$('.link_course').hide();
			$('.'+filter).show();
			if (!$('#test').prop('checked')){
				$('.type_1').hide();
			}
			if (!$('#demo').prop('checked')){
				$('.type_2').hide();
			}
		}
	}
</script>

<?php if($_SESSION['user_role'] == 1000){?>

<ul class="nav nav-list">
	<li class="nav-header">Aggiungi nuovo</li>
	<li <?php if($page == "new-course"){ ?> class="active" <?php } ?>><a
		href="new-course.php"><img alt="nuovo corso" src="img/new_company.png"
			title="inserisci un nuovo corso"> Crea corso</a>
	</li>
</ul>

<?php }?>

<ul class="nav nav-list course-list">
	<li class="nav-header">Elenco corsi</li>
	<li><?php if ($_SESSION['user_role'] == 1000){ ?>

		<ul class="nav nav-pills course-filter">
			<li class="active" onclick="filterCourse('on_sale')"><a
				href="javascript: void(0)">Attivi</a>
			
			<li>
			
			<li onclick="filterCourse('suspended')"><a href="javascript: void(0)">sospesi</a>
			
			<li>
			
			<li onclick="filterCourse('open')"><a href="javascript: void(0)">Non
					pubblicati</a>
			
			<li>
			
			<li onclick="filterCourse('all')"><a href="javascript: void(0)">Tutti</a>
			
			<li>
		
		</ul>
	</li>
	<li><input type="checkbox" id="demo" class="filter-om">
            <label class="filter-om">Demo</label> 
            <input type="checkbox" id="test" class="filter-om">
            <label class="filter-om">Test</label> 
            <?php }?> 
            <input type="checkbox" id="customs" class="filter-om">
            <label class="filter-om">Personalizzati</label>
	</li>
	<li style="background-color: yellow">
		CATALOGO ECOMMERCE (<?=sizeof($learning_list)?>)
	</li>

	<?php 
        foreach($learning_list as $single){
            //$learn = $course_obj->getLearningProjectFromCourse($single['id']);
            if ($_SESSION['user_role'] != 1000){
                if (!isset($single['is_published_in_ecommerce']) || !$single['is_published_in_ecommerce']) continue;
                if ($single['reserved_to'] != ""){
                    if ($_SESSION['user_role'] == 1 ){
                        // tutor
                        $view_enabled = false;
                        $reserved = explode(",",$single['reserved_to']);
                        foreach ($companies_tutor as $company){
                                if (in_array($company['id'], $reserved)) {
                                        $view_enabled = true;
                                        break;
                                }
                        }
                        if (!$view_enabled) continue;
                    } else {
                        // azienda no tutor
                        if (!isset($single['reserved_to']) || !in_array($_SESSION['user_company_id'], explode(",",$single['reserved_to']))) continue;
                    }
                }
                $learn_class = ' on_sale';
            } elseif (!isset($single['is_published_in_ecommerce'])){
                $learn_class = ' open';
            } else {
                $learn_class = $single['is_published_in_ecommerce']?' on_sale':' suspended';
            }
    	?>

	<style>
		/*.titleCourseDetail{ display: none;}*/
		/*.displayMenuList{ display: block;}*/
	</style>

	<li id="link_<?=$single['id']?>" class="link_course<?php if($id == $single['id']){?> active<?php } ?>
                    <?= $learn_class?>
                    <?= "type_{$single['type_id']} custom_{$single['custom']}"?>
                    <?= $single['learning_project_id'] ? ' published' : ''?>">
		<button type="button" class="btn btn-xs" data-target="#titleCourseDetail_<?=$single['id']?>"  data-toggle="collapse"
				style="padding: 1px 3px; width:30px; display: inline; margin-right: 15px; border: none; background: none; color: #000;"> +
		</button>
		<a style="<?php if ($single["Tipo"] == 'nuovo') echo 'color: #AAD178;'; ?> display: inline; padding-left:5px;" href="course-management.php?id=<?=$single['id']?>" data-course_id="<?=$single['id']?>">
			<?=strtoupper($single['lerningtitle'])?> - Ore <?=$single["total_elearning"]?>
		</a>



        <div class="titleCourseDetail collapse" id="titleCourseDetail_<?=$single['id']?>">
            Course id: <?=$single["course_id"]?><br />
            Learning id: <?=$single["learning_project_id"]?><br />
            Categoria: <b><?=$single["category"]?></b><br />
            Sottocategoria: <?=$single["subcategory"]?><br />
            Destinazione: <?=$single["type"]?><br />
        </div>

	</li>

	<?php }
	?>

	<li style="background-color: yellow">
		Courses
	</li>
	<?php
	foreach($course_list as $single){
		//$learn = $course_obj->getLearningProjectFromCourse($single['id']);
		if ($_SESSION['user_role'] != 1000){
			if (!isset($single['is_published_in_ecommerce']) || !$single['is_published_in_ecommerce']) continue;
			if ($single['reserved_to'] != ""){
				if ($_SESSION['user_role'] == 1 ){
					// tutor
					$view_enabled = false;
					$reserved = explode(",",$single['reserved_to']);
					foreach ($companies_tutor as $company){
						if (in_array($company['id'], $reserved)) {
							$view_enabled = true;
							break;
						}
					}
					if (!$view_enabled) continue;
				} else {
					// azienda no tutor
					if (!isset($single['reserved_to']) || !in_array($_SESSION['user_company_id'], explode(",",$single['reserved_to']))) continue;
				}
			}
			$learn_class = ' on_sale';
		} elseif (!isset($single['is_published_in_ecommerce'])){
			$learn_class = ' open';
		} else {
			$learn_class = $single['is_published_in_ecommerce']?' on_sale':' suspended';
		}
		?>

		<li id="link_<?=$single['id']?>"
			class="link_course<?php if($id == $single['id']){?> active<?php } ?>
                    <?=$learn_class?>
                    <?=" type_{$single['type_id']} custom_{$single['custom']}"?>
                    <?= $single['learning_project_id'] ? ' published' : ''?>">
			<a href="course-management.php?id=<?=$single['id']?>" data-course_id="<?=$single['id']?>">
				<?=$single['id']?> - <?=strtoupper($single['title'])?>
			</a>
		</li>

	<?php }
	?>

</ul>

<script>
	adjustHeight("left_menu",140);
	
	$('.course-filter li').click(function(e){
		$(this).siblings().removeClass('active');
		$(this).addClass('active');
	});

	$('#demo').click(function(){
		if ($(this).prop('checked')){
			$('.type_2').show();
		} else {
			$('.type_2').hide();
		}
	});

	$('#test').click(function(){
		if ($(this).prop('checked')){
			$('.type_1').show();
		} else {
			$('.type_1').hide();
		}
	});

	$('#customs').click(function(){
		if ($(this).prop('checked')){
			$('.custom_1').show();
		} else {
			$('.custom_1').hide();
		}
	});


	$(function() {
		filterCourse('on_sale');
		$('.type_1').hide();
		$('.type_2').hide();
		$('.custom_1').hide();
	});
</script>
