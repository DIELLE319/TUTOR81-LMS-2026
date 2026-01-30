/**
 * Verifica validità del codice di controllo della partita IVA.
 * Il valore vuoto NON è "valido".
 * Per aggiornamenti e ulteriori info v. http://www.icosaedro.it/cf-pi
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version 2016-12-05
 * @param string pi Partita IVA da controllare.
 * @return string Stringa vuota se il codice di controllo è
 * corretto oppure il valore è vuoto, altrimenti un messaggio
 * che descrive perché il valore non può essere valido.
 */
function ControllaPIVA(pi)
{
	//if( pi == '' )  return '';
	if( pi == '' || ! /^[0-9]{11}$/.test(pi) )
		return "La partita IVA deve contenere 11 cifre.";
	var s = 0;
	for( i = 0; i <= 9; i += 2 )
		s += pi.charCodeAt(i) - '0'.charCodeAt(0);
	for(var i = 1; i <= 9; i += 2 ){
		var c = 2*( pi.charCodeAt(i) - '0'.charCodeAt(0) );
		if( c > 9 )  c = c - 9;
		s += c;
	}
	var atteso = ( 10 - s%10 )%10;
	if( atteso != pi.charCodeAt(10) - '0'.charCodeAt(0) )
		return "La partita IVA non è valida:\n" +
			"il codice di controllo non corrisponde.\n";
	return '';
}

function controllaCF(g) {
    var b, d, f, e, c, a, h;
    if (g == "") {
        return false;
    }
    g = g.toUpperCase();
    if (g.length != 16) {
        return false;
    }
    b = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    for (d = 0; d < 16; d++) {
        if (b.indexOf(g.charAt(d)) == -1) {
            return false;
        }
    }
    e = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    c = "ABCDEFGHIJABCDEFGHIJKLMNOPQRSTUVWXYZ";
    a = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    h = "BAKPLCQDREVOSFTGUHMINJWZYX";
    f = 0;
    for (d = 1; d <= 13; d += 2) {
        f += a.indexOf(c.charAt(e.indexOf(g.charAt(d))));
    }
    for (d = 0; d <= 14; d += 2) {
        f += h.indexOf(c.charAt(e.indexOf(g.charAt(d))));
    }
    if (f % 26 != g.charCodeAt(15) - "A".charCodeAt(0)) {
        return false;
    }
    return true;
}
function validateEmail(a) {
    var b = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return b.test(a);
}

function valBetween(v, min, max) {
    return (Math.min(max, Math.max(min, v)));
}

function validateMinMax(element) {
    element.value = valBetween(element.value, element.min, element.max);
}

function convertArrayOfObjectsToCSV(f) {
    var k, a, j, g, h, e, c;
    c = f.data || null;
    if (c == null || !c.length) {
        return null;
    }
    g = f.columnDelimiter || ",";
    h = f.lineDelimiter || "\n";
    e = f.textDelimiter || '"';
    j = Object.keys(c[0]);
    var d = new Array();
    for (var b = 0; b < j.length; b++) {
        d[b] = e + j[b] + e;
    }
    k = "";
    k += d.join(g);
    k += h;
    c.forEach(function(i) {
        a = 0;
        j.forEach(function(l) {
            if (a > 0) {
                k += g;
            }
            k += e + i[l] + e;
            a++;
        });
        k += h;
    });
    return k;
}
function downloadCSV(c) {
    var e, b, d;
    var a = c.data;
    if (a == null) {
        return;
    }
    b = c.filename || "export.csv";
    if (!a.match(/^data:text\/csv/i)) {
        a = "data:text/csv;charset=utf-8," + a;
    }
    e = encodeURI(a);
    d = document.createElement("a");
    d.setAttribute("href", e);
    d.setAttribute("download", b);
    d.click();
}
function stopRKey(a) {
    var a = (a) ? a : ((event) ? event : null);
    var b = (a.target) ? a.target : ((a.srcElement) ? a.srcElement : null);
    if ((a.keyCode == 13) && (b.type == "text")) {
        return false;
    }
}
function taxcodeExist(c, a) {
    var a = typeof a !== "undefined" ? a : 0;
    var b = false;
    $.ajax({
        url: "manage/user.php",
        type: "POST",
        data: {
            op_type: "check_tax_code",
            tax_code: c,
            user_id: a
        },
        async: false,
        success: function(d) {
            b = d > 0 ? true : false;
        }
    });
    return b;
}
function getCompanyLicenseDetail(a) {
    var b = false;
    $.ajax({
        async: false,
        cache: false,
        type: "POST",
        url: "manage/company.php",
        data: {
            op_type: "get_current_license",
            company_id: a
        }
    }).done(function(c) {
        b = c != 0 ? $.parseJSON(c) : false;
    });
    return b;
}
function suspendedCompanyLicense(b, a) {
    $.post("manage/company.php", {
        op_type: "suspended_company_license",
        id_license: b,
        suspended: a
    });
}
function calcElearningPurchaseUnassigned(b, c) {
    var a = 0;
    $.ajax({
        async: false,
        cache: false,
        type: "POST",
        url: "manage/license.php",
        data: {
            op_type: "get_elearning_purchase_unassigned",
            company_id: b,
            learning_project_id: c
        }
    }).done(function(d) {
        a = d;
    });
    return a;
}
function calcElearningPacked(b, c) {
    var a = 0;
    $.ajax({
        async: false,
        cache: false,
        type: "POST",
        url: "manage/pack.php",
        data: {
            op_type: "calc_elearning_packed",
            company_id: b,
            learning_project_id: c
        }
    }).done(function(d) {
        a = d;
    });
    return a;
}
function fitMainMenu() {
    var c = parseInt($("body").css("padding-top"));
    var d = $(document).height() - c;
    var a = $(window).height() - c;
    var b = parseInt($("#footer").css("height"));
    if (a < (d - b)) {
        $(".main-menu-body").css("max-height", (a - 20 - b - $(".col-menu-title").height() - parseInt($(".main-menu-heading").css("height")) - 2) + "px");
    } else {
        $(".main-menu-body").css("max-height", (d - b - 20 - $(".col-menu-title").height() - parseInt($(".main-menu-heading").css("height")) - 2) + "px");
    }
}
function fitHomeAccordionPanelCollpase() {
    var a = $(window).height();
    var d = parseInt($("body").css("padding-top"));
    var c = parseInt($("#footer").css("height"));
    var b = 0;
    $("#home-accordion > .panel").each(function() {
        b += parseInt($(this).css("margin-top")) + parseInt($(this).find(".panel-heading").css("height")) + 2;
    }).promise().done(function() {
        $("#home-accordion > .panel .panel-collapse").css("max-height", (a - d - b - c - 25) + "px");
    });
    $("#users-list-table").parent().css("height", ($("#collapse-employees").height() - $("#collapse-employees .nav-filter").height() - 20) + "px");
}
function getFilterInput() {
    var a = [];
    $(".tablesorter-filter").each(function(b) {
        a[$(this).data("column")] = $(this).val();
    });
    return a;
}
function showDetail(b, c, a) {
    $("#sub-container").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>');
    if (a) {
        $("#sub-container").load(b, {
            id: c,
            setting: a
        });
    } else {
        $("#sub-container").load(b, {
            id: c
        });
    }
}
function resetUserPassword(a) {
    if (confirm("Verrà reimpostato il codice fiscale come password e verrà inviato un avviso per posta elettronica. Procedo?")) {
        $.isLoading({
            text: "Attendere il completamento ..."
        });
        $.post("manage/user.php", {
            op_type: "reset_user_password",
            user_id: a
        }, function(b) {
            $.isLoading("hide");
            if (b > 0) {
                alert("La password è stata reimpostata ed è stato inviato un avviso per posta elettronica.");
            } else {
                alert("La password è già stata reimpostata precedentemente e non è ancora stata modificata dall'utente.");
            }
        });
    }
}
function sendUserName(a) {
    if (confirm("Il nome utente verrà inviato per email. Vuoi procedere?")) {
        $.isLoading({
            text: "Attendere il completamento ..."
        });
        $.post("manage/user.php", {
            op_type: "send_user_name",
            user_id: a
        }, function(b) {
            $.isLoading("hide");
            if (b > 0) {
                alert("Il nome utente e stato inviato per posta elettronica");
            } else {
                alert("errore nell'invio del nome utente: " + b);
            }
        });
    }
}
function createCompany() {
    /* *** VALIDAZIONE *** */
    /* validazione azienda */
    var business_name = $("#business_name").val();
    var vat = $("#vat").val();
    var address = $("#address").val();
    var postal_code = $("#postal_code").val();
    var city = $("#city").val();
    var province_id = $("#province_id").val();
    var email = $("#email").val();
    if (business_name == "" || vat == "" || address == "" || postal_code == "" || city == "" || province_id == "" || email == "") {
        alert('Compilare tutti i campi obbligatori (segnati con *).');
        return false;
    }
    
    var owner_user_id = $('[name="owner_user_id"]').val();
    var is_tutor = $('input[name="is_tutor"]').val();
    
    var existent_user_id = $("#search-user").data("user_id");
    var new_admin = $("#new-admin").prop("checked");
    if (new_admin){
        /* validazione amministratore-referente */
        var admin_name = $("#admin_name").val();
        var admin_surname = $("#admin_surname").val();
        var admin_tax_code = $("#admin_tax_code").val();
        var admin_email = $("#admin_email").val();
        if (admin_name == "" || admin_surname == "" || admin_tax_code == "" || admin_email == ""){
            alert('Compilare tutti i campi obbligatori (segnati con *).');
            return false;
        }

        $.ajax({
            url: "manage/user.php",
            type: "POST",
            data: {
                op_type: 'check_tax_code',
                tax_code: admin_tax_code,
                user_id: 0
            },
            async: false,
            success: function (data) {
                if (data > 0) {
                    alert("Il codice fiscale dell'amministratore esiste già");
                    return false;
                }
            }
        });

        if (!validateEmail(admin_email)) {
            alert("L'indirizzo email dell'amministratore non è valido");
            return false;
        }
    } else if(is_tutor == 1 && existent_user_id == 0) {
        alert('selezionare un amministratore o crearne uno.');
        return false;
    }
    
    var new_didactic_tutor = $('#new-didactic').prop('checked');
    if (new_didactic_tutor){
        /* validazione tutor didattico */
        var td_name = $('#td_name').val();
        var td_surname = $('#td_surname').val();
        var td_tax_code = $('#td_tax_code').val();
        var td_email = $('#td_email').val();
        if (td_name == "" || td_surname == "" || td_tax_code == "" || td_email == ""){
            alert('Compilare tutti i campi obbligatori (segnati con *).');
            return false;
        }
        
        $.ajax({
            url: "manage/user.php",
            type: "POST",
            data: {
                op_type: 'check_tax_code',
                tax_code: td_tax_code,
                user_id: 0
            },
            async: false,
            success: function (data) {
                if (data > 0) {
                    alert("Il codice fiscale del tutor didattico esiste già");
                    return false;
                }
            }
        });
        
        if (!validateEmail(td_email)) {
            alert("L'indirizzo email del tutor didattico non è valido");
            return false;
        }
    }
    
    var risk_evaluation = $('#risk_evaluation')[0].checked;
    var trainer = $('#trainer').val();
    var ateco_sector_id = $("#new_ateco").val();
    if (risk_evaluation) {
        /* validazione valutazione rischi */
        if (ateco_sector_id == 1) {
            alert('Selezionare un settore ateco per la valutazione dei rischi.');
            return false;
        }
    }
    
    var created_by = $('input[name="created_by"]').val();
    var is_partner = $('input[name="is_partner"]').val();
    var tutor_id = $('input[name="tutor_id"]').val();
    var telephone = $("#telephone").val();
    var gmt = $("#gmt").val();
    var discount = 0;//$("#new_discount").val();
    var contract_id = 0;//$('#contract').val();
    var regional_authorization = $('#showRegionalField')[0].checked ? $('#regional_authorization').val() : '';
    var ateco = $('#ateco').val();
    var test_in_the_presence = $('#test_in_the_presence')[0].checked ? $('input[name="test_in_the_presence"]:checked').val() : 'NO';
    var iban = $('#iban').val();
    var admin_id = 0;
    var company_id = 0;
    //piani di abbonamento
    var plan = {
            plan_id: 6, 
            validity_start: false, 
            validity_end:false, 
            discount: 0, 
            ecommerce: 0, 
            customized_courses: 0, 
            max_admin: 0, 
            max_concurrent_users: 0, 
            price: 0
        };
    //plan.validity_start = new Date();
    //plan.validity_end = new Date();
    //plan.validity_end.setFullYear(plan.validity_start.getFullYear() + 1);
    if (is_tutor == 1){
        plan.plan_id = $('#plan_id').val();
        plan.validity_start = $('#validity_start').datepicker("getDate");
        plan.validity_end = $('#validity_end').datepicker("getDate");
        plan.discount = $('#discount').val();
        plan.ecommerce = $("input[name='ecommerce']:checked").val();
        plan.customized_courses = $("input[name='customized_courses']:checked").val();
        plan.max_admin = $('#max_admin').val();
        plan.max_concurrent_users = $('#max_concurrent_users').val();
        plan.price = $('#price').val();
        /* validazione piano abbonamento */
        if (plan.plan_id == 0) {
            alert('Selezionare un piano di abbonamento.');
            return false;
        }
    }
    
    $.ajax({
        url: "manage/company.php",
        type: "POST",
        data: {
            op_type: 'nuova_company',
            created_by: created_by,
            is_tutor: is_tutor,
            is_partner: is_partner,
            tutor_id: tutor_id,
            owner_user_id: owner_user_id > 0 ? owner_user_id : 6,
            business_name: business_name,
            vat: vat,
            address: address,
            postal_code: postal_code,
            city: city,
            province_id: province_id,
            telephone: telephone,
            email: email,
            discount: discount,
            gmt: gmt,
            ateco_sector_id: ateco_sector_id,
            contract_id: contract_id,
            regional_authorization: regional_authorization,
            trainer: trainer,
            ateco: ateco,
            test_in_the_presence: test_in_the_presence,
            risk_evaluation: risk_evaluation ? 1 : 0,
            iban: iban,
            plan: JSON.stringify(plan)
        },
        async: false,
        success: function (data) {
            //alert('data add company: ' + data);
            if (data == "PIVA") {
                alert("Partita iva già esistente");
                return false;
            } else if (data > 0) {
                $('.isloading-wrapper').append('<br>Creata nuova azienda.')
                company_id = data;
                var tutor_didactic_id = $('#tutor-didactic').data('tutor-didactic');
                if (new_admin){
                    // salva nuovo amministratore - responsabile
                    var admin_send_mail = $('input[name="admin_send_mail"]').prop('checked');
                    $.ajax({
                        url: "manage/user.php",
                        type: "POST",
                        data: {
                            op_type: 'nuovo_utente',
                            assignto: created_by,
                            role: (is_tutor ? 1 : 2),
                            name: admin_name,
                            surname: admin_surname,
                            email: admin_email,
                            tax_code: admin_tax_code,
                            send_mail: admin_send_mail,
                            func_id: 1,
                            company_id: company_id
                        },
                        async: false,
                        success: function (data) {
                            admin_id = data;
                            var type_admin = is_tutor ? 'amministratore dell\'ente formativo' : 'responsabile aziendale';
                            $('.isloading-wrapper').append('<br>Creato nuovo ' + type_admin + '.');
                            if($('#didactic-selection input:checked').val() == 2) tutor_didactic_id = admin_id;
                            if (is_tutor){
                                owner_user_id = admin_id;
                            }
                        },
                        error: function(data, status, err){
                            var type_admin = is_tutor ? 'dell\'amministratore dell\'ente formativo' : 'del responsabile aziendale';
                            $('.isloading-wrapper').append('<br>Errore nella creazione ' + type_admin + '. Verificare i dati inseriti e aggiungerlo successivamente.');
                        }
                    });
                } else if (is_tutor && existent_user_id > 0){
                    owner_user_id = existent_user_id;
                    // sposta utente nell'ente appena creato 
                    // cancella la sua storia di appartenenza a dipartimenti e 
                    // reparti dell'azienda di provenienza
                    // cambia il ruolo dell'utente in amministratore
                    $.ajax({
                        url: "manage/user.php",
                        type: "POST",
                        data: {
                            op_type: 'change_user_company',
                            user_id: existent_user_id,
                            company_id: company_id
                        },
                        async: true
                    });
                    $.ajax({
                        url: "manage/department.php",
                        type: "POST",
                        data: {
                            op_type: 'delete_history_employee_departments',
                            user_id: existent_user_id
                        },
                        async: true
                    });
                    $.ajax({
                        url: "manage/user.php",
                        type: "POST",
                        data: {
                            op_type: 'change_role',
                            id: existent_user_id,
                            role: 1
                        },
                        async: true
                    });
                }
                if (is_tutor == 1) {
                    $.ajax({
                        url: "manage/company.php",
                        type: "POST",
                        data: {
                            op_type: 'set_owner',
                            company_id: company_id,
                            owner_user_id: owner_user_id
                        },
                        async: true
                    });
                    if (!$("#type_company").prop("checked")) {
                        $.ajax({
                            url: "manage/company.php",
                            type: "POST",
                            data: {
                                op_type: 'nuova_company',
                                created_by: created_by,
                                is_tutor: 0,
                                is_partner: 0,
                                tutor_id: tutor_id,
                                owner_user_id: owner_user_id,
                                business_name: 'test ' + business_name,
                                vat: 'T' + vat,
                                address: address,
                                postal_code: postal_code,
                                city: city,
                                province_id: province_id,
                                telephone: telephone,
                                email: email,
                                discount: discount,
                                gmt: gmt,
                                ateco_sector_id: ateco_sector_id,
                                contract_id: contract_id,
                                regional_authorization: regional_authorization,
                                trainer: trainer,
                                ateco: ateco,
                                test_in_the_presence: test_in_the_presence,
                                risk_evaluation: risk_evaluation ? 1 : 0,
                                iban: iban
                            },
                            async: true
                        });
                    }
                }
                if (new_didactic_tutor){
                    // salva utente nuovo per tutor didattico
                    var td_send_mail = $('input[name="td_send_mail"]').prop('checked');
                    $.ajax({
                        url: "manage/user.php",
                        type: "POST",
                        data: {
                            op_type: 'nuovo_utente',
                            assignto: created_by,
                            role: 0,
                            name: td_name,
                            surname: td_surname,
                            email: td_email,
                            tax_code: td_tax_code,
                            send_mail: td_send_mail,
                            func_id: 1,
                            company_id: company_id
                        },
                        async: false,
                        success: function (data) {
                            tutor_didactic_id = data;
                            $('.isloading-wrapper').append('<br>Creato nuovo utente per tutor didattico.');
                        },
                        error: function(data, status, err){
                            $('.isloading-wrapper').append('<br>Errore nella creazione del tutor didattico.')
                            .append('<br>Verificare i dati e aggiungerlo successivamente.');
                        }
                    });
                } else if (is_tutor == 1 && $('#existent').prop('checked') && existent_user_id > 0) {
                    tutor_didactic_id = existent_user_id;
                }
                // salva tutor didattico
                $.ajax({
                    url: "manage/company.php",
                    type: "POST",
                    data: {
                        op_type: "add_didactic_tutor",
                        comp_id: company_id,
                        tutor_didactic_id: tutor_didactic_id
                    },
                    async: false,
                    success: function (data) {
                        $('.isloading-wrapper').append('<br>Assegnato tutor didattico all\'azienda.');
                    }
                });
            } else {
                alert("L'azienda non è stata creata. Verificare tutti i dati e riprovare");
                return false;
            }
        }
    });
    if (risk_evaluation){
        // salva valutazione rischi
        //$('.isloading-wrapper').append('<br>Salvata valutazione rischi.');
    }
    return company_id;
}
function createCompanyShort() {
    var n = $("#business_name").val();
    var m = $("#vat").val();
    var k = $("#email").val();
    var g = $("#telephone").val();
    var e = $("#iban-register").val();
    var a = $("#licence").val();
    var d = $("#admin_name").val();
    var b = $("#admin_surname").val();
    var c = $("#admin_tax_code").val();
    var i = $("#admin_email").val();
    var j = $("#admin_telephone").val();
    var l = $("#is_tutor").val();
    var f = 0;
    var h = {
        business_name: n,
        vat: m,
        telephone_company: g,
        email_company: k,
        name: d,
        surname: b,
        email: i,
        tax_code: c,
        telephone: j,
        iban: e,
        licence: a,
        admin_name: d,
        admin_surname: b,
        admin_tax_code: c,
        admin_email: i,
        admin_telephone: j,
        is_tutor: l,
        op_type: "nuova_company"
    };
    console.log(h);
    $.ajax({
        url: "register_company_user.php",
        type: "POST",
        data: h,
        async: false,
        success: function(o) {
            console.log("Dopo invio...");
            console.log(o);
            if (o == "PIVA") {
                alert("Partita iva già esistente");
                return false;
            } else {
                if (o == "UTENTE") {
                    alert("Codice fiscale dell'utente già utilizzato.");
                    return false;
                } else {
                    if (o > 0) {
                        $(".isloading-wrapper").append("<br>Creata nuova azienda.");
                        f = o;
                    } else {
                        console.log(o);
                        alert("L'azienda non è stata creata. Verificare tutti i dati e riprovare");
                        return false;
                    }
                }
            }
        }
    });
    return f;
}
function newUser(d, l) {
    var c = $('#new-employee .employee-detail input[name="name"]').val();
    var i = $('#new-employee .employee-detail input[name="surname"]').val();
    var f = $('#new-employee .employee-detail input[name="tax_code"]').val();
    var k = $('#new-employee .employee-detail input[name="email"]').val();
    var j = $('#new-employee .employee-detail select[name="business_function"]').val();
    var h = $('#new-employee .employee-department select[name="product_unit"]').val();
    var g = $('#new-employee .employee-department select[name="department"]').val();
    if (c == "" || i == "" || k == "" || f == "") {
        alert("Compila tutti i campi obbligatori *.");
        return false;
    }
    if (!controllaCF(f)) {
        alert("Il codice fiscale immesso non è valido.");
        return false;
    }
    if (taxcodeExist(f)) {
        alert("Il codice fiscale immesso è uguale a quello di un altro utente. Modificalo e salva nuovamente.");
        return false;
    }
    if (!validateEmail(k)) {
        alert("L'email inserita non è valida.");
        return false;
    }
    var b = $('#new-employee .employee-department input[name="hire-date"]').datepicker("getDate");
    var a = new Array();
    var e = "";
    $("#new-employee .employee-assignments tbody > tr").each(function() {
        var n = $(this).find("input.start_date").datepicker("getDate");
        var m = $(this).find("input.end_date").datepicker("getDate");
        a.push({
            assign_id: $(this).data("assignment"),
            start_date: $(this).find("input.start_date").val() != "" && n instanceof Date && !isNaN(n.valueOf()) ? $.datepicker.formatDate("yy-mm-dd", n) : "",
            end_date: $(this).find("input.end_date").val() != "" && m instanceof Date && !isNaN(m.valueOf()) ? $.datepicker.formatDate("yy-mm-dd", m) : ""
        });
    }).promise().done(function() {
        $.ajax({
            type: "POST",
            url: "manage/user.php",
            data: {
                op_type: "nuovo_utente",
                company_id: d,
                send_mail: $("#new-employee .control-send-email input:checked").val(),
                name: c,
                surname: i,
                email: k,
                tax_code: f,
                role: $('#new-employee .control-role input[name="role"]:checked').val(),
                func_id: j,
                assignto: l,
                assignments: a,
                dep_id: g,
                hire_date: $('#new-employee .employee-department input[name="hire-date"]').val() != "" && b instanceof Date && !isNaN(b.valueOf()) ? $.datepicker.formatDate("yy-mm-dd", b) : ""
            },
            async: false
        }).done(function(m) {
            if (m > 0) {
                e = {
                    id: m,
                    name: c,
                    surname: i,
                    tax_code: f,
                    business_function: $('#new-employee select[name="business_function"] > option[value="' + j + '"]').text(),
                    email: k,
                    product_unit: $('#new-employee select[name="product_unit"] > option[value="' + h + '"]').text(),
                    department: $('#new-employee select[name="department"] > option[value="' + g + '"]').text()
                };
            } else {
                alert("Errore nella registrazione dei dati: " + m);
                e = false;
            }
        });
    });
    return e;
}
function createUsers(b, a) {
    var d = $("#control-users tbody tr").length;
    var c = false;
    $("#control-users tbody tr").each(function(i) {
        var h = $(this).find('td input[id^="new_name_"]').val();
        var l = $(this).find('td input[id^="new_surname_"]').val();
        var j = $(this).find('td input[id^="new_cf_"]').val();
        var e = $(this).find('td input[id^="new_email_"]').val();
        if (h == "" || l == "" || e == "" || j == "") {
            alert("Compila tutti i campi obbligatori.");
            return false;
        }
        if (!controllaCF(j)) {
            $(this).find('td input[id^="new_cf_"]').parent().addClass("has-error");
            alert("Codice fiscale " + j + " non valido. Controlla bene i dati inseriti.");
            return false;
        } else if (taxcodeExist(j)) {
            $(this).find('td input[id^="new_cf_"]').parent().addClass("has-error");
            alert("Il codice fiscale " + j + " è uguale a quello di un altro utente. Modificalo e salva nuovamente.");
            return false;
        } else if (!validateEmail(e)) {
            $(this).find('td input[id^="new_email_"]').parent().addClass("has-error");
            alert("L'indirizzo email " + e + " non è valido. Controlla bene i dati inseriti.");
            return false;
        } else {
            $(this).find('td').removeClass("has-error");
        }
        if (i == d - 1) {
            var k = $("#control-users tbody tr").map(function() {
                return JSON.stringify({
                    name: $(this).find('td input[id^="new_name_"]').val(),
                    surname: $(this).find('td input[id^="new_surname_"]').val(),
                    tax_code: $(this).find('td input[id^="new_cf_"]').val(),
                    email: $(this).find('td input[id^="new_email_"]').val(),
                    product_unit: $(this).find('td input[id^="new_product_unit_"]').val(),
                    department: $(this).find('td input[id^="new_department_"]').val(),
                    hire_date: $(this).find('td input[id^="new_hire_date_"]').val(),
                    role: 0,
                    func_id: $('#import_employees input[name="funzione"]:checked').val(),
                    assignto: a
                });
            }).get();
            $.ajax({
                type: "POST",
                url: "manage/user.php",
                async: false,
                data: {
                    op_type: "add_multiple_users",
                    company_id: b,
                    users_data: k
                }
            }).done(function(m) {
                c = m;
            });
        }
    });
    if (c === false) {
        return false;
    } else {
        if (c === 0) {
            alert("Non è stato creato alcun utente. Verifica i dati e riprova.");
            return false;
        } else {
            var g = "";
            try {
                g = JSON.parse(c);
                return g;
            } catch (f) {
                alert("Errore nella creazione degli utenti: " + c);
                return false;
            }
        }
    }
}
function editUser(a) {
    var c = $('#edit-employee .employee-detail input[name="name"]').val();
    var f = $('#edit-employee .employee-detail input[name="surname"]').val();
    var e = $('#edit-employee .employee-detail input[name="tax_code"]').val();
    var b = $('#edit-employee .employee-detail input[name="email"]').val();
    var g = $('#edit-employee .employee-detail input[name="nu"]').val();
    var d = $('#edit-employee .employee-detail input[name="hire_date"]').datepicker("getDate");
    if (c == "" || f == "" || b == "" || e == "" || g == "") {
        alert("Compila tutti i campi obbligatori *.");
        return false;
    }
    if (!controllaCF(e)) {
        alert("Il codice fiscale immesso non è valido. LA MODIFICA NON VERRA' SALVATA!.");
        return false;
    }
    if (taxcodeExist(e, a)) {
        alert("Il codice fiscale immesso è uguale a quello di un altro utente. Modificalo.");
        return false;
    }
    if (!validateEmail(b)) {
        alert("Indirizzo email non valido. LA MODIFICA NON VERRA' SALVATA!.");
        return false;
    }
    $.post("manage/user.php", {
        op_type: "edit_utente",
        user_id: a,
        name: c,
        surname: f,
        email: b,
        tax_code: e,
        username: g,
        role: $('#edit-employee .employee-detail input[name="role"]:checked').val(),
        func_id: $('#edit-employee .employee-detail select[name="business_function"]').val()
    }, function(h) {
        if (h == "UTENTE") {
            alert("Il codice fiscale immesso è uguale a quello di un altro utente. Modificalo e salva nuovamente.")
        } else {
            if (h > 0) {
                alert("Modifiche salvate correttamente");
                $("#edit-employee").parent().html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>').load("pages/sections/employee-detail.php?user_id=" + a);
            } else {
                if (h == 0) {
                    alert("Non è stato modificato nessun dato");
                } else {
                    alert("Errore nella registrazione dei dati. Riprova.\n" + h);
                }
            }
        }
    });
}
function disableUser(a) {
    var b = false;
    $.ajax({
        type: "POST",
        url: "manage/user.php",
        async: false,
        data: {
            op_type: "remove_utente",
            id: a
        }
    }).done(function(c) {
        b = c > 0 ? true : false;
    });
    return b;
}
function enableUser(a) {
    var b = false;
    $.ajax({
        type: "POST",
        url: "manage/user.php",
        async: false,
        data: {
            op_type: "enable_user",
            id: a
        }
    }).done(function(c) {
        b = c > 0 ? true : false;
    });
    return b;
}
function deleteUser(b) {
    var a = false;
    $.ajax({
        type: "POST",
        url: "manage/user.php",
        async: false,
        data: {
            op_type: "delete_user",
            id: b
        }
    }).done(function(c) {
        a = c > 0 ? true : false;
    });
    return a;
}
function deleteCompany(b) {
    var a = false;
    $.ajax({
        type: "POST",
        url: "manage/company.php",
        async: false,
        data: {
            op_type: "delete_company",
            company_id: b
        }
    }).done(function(c) {
        a = c > 0 ? true : false;
    });
    return a;
}
function showElearningDetail(a) {
    showDetail("elearning.php", a);
}
function showReportDetail(a) {
    showDetail("report-management.php", a);
}
function showSpecificReport(c, a, b) {
    $(".link_az").removeClass("active");
    $("#link_" + c).addClass("active");
    $("#sub-container").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>');
    $("#sub-container").load("report-management.php", {
        id: c,
        report_type: a,
        course_id: b
    });
}
function sendLicenseCourse(a) {
    $.post("manage/user.php", {
        op_type: "send_license",
        license_id: a
    }, function(b) {
        alert("Licenza corso inviata con successo.");
    });
}
function notifyCourseAssignment(a) {
    if (confirm("Vuoi informare il corsista dell'assegnazione di questo corso?")) {
        $.isLoading({
            text: "Attendere il completamento ..."
        });
        $.post("manage/license.php", {
            op_type: "notify_course_assignment",
            license_id: a
        }, function(b) {
            $.isLoading("hide");
            if (b > 0) {
                alert("Notifica inviata con successo.");
            } else {
                alert("La notifica non è stato inviata");
            }
        })
    }
}
function sendAlertCourse(b, a) {
    $.isLoading({
        text: "Attendere il completamento ..."
    });
    $.post("manage/license.php", {
        op_type: "send_alert",
        license_id: b,
        custom_message: a
    }, function(c) {
        $.isLoading("hide");
        if (c > 0) {
            alert("Alert inviato con successo.");
        } else {
            alert("L'alert non è stato inviato");
        }
    });
}
function getLearningPrice(b) {
    var a = false;
    $.ajax({
        asyn: false,
        url: "manage/learning_project.php",
        data: {
            op_type: "get_learning_project_price",
            learning_project_price: b
        },
        success: function(c) {
            try {
                a = $.parseJSON(c);
            } catch (d) {
                a = false;
            }
        }
    });
    return a;
}
function showCompanyDetail(b, a) {
    showDetail("company-management.php", b, a);
}
function showSafetyDetail(b, a) {
    showDetail("safety-management.php", b, a);
}
function onChangeFireRisk(b) {
    var a = $('select[name="fire_risk"]').data("fire_risk");
    $.post("manage/department.php", {
        op_type: a == "" ? "add_product_unit_custom_category" : "edit_product_unit_custom_category",
        pu_id: b,
        id_pu_ccat: a,
        ccat_id: $('select[name="fire_risk"]').val()
    }, function(c) {
        if (c > 0 && a == "") {
            $('select[name="fire_risk"]').data("fire_risk", c);
        }
    });
}
function onChangeFirstAidRisk(b) {
    var a = $('select[name="first_aid_risk"]').data("first_aid_risk");
    $.post("manage/department.php", {
        op_type: a == "" ? "add_product_unit_custom_category" : "edit_product_unit_custom_category",
        pu_id: b,
        id_pu_ccat: a,
        ccat_id: $('select[name="first_aid_risk"]').val()
    }, function(c) {
        if (c > 0 && a == "") {
            $('select[name="first_aid_risk"]').data("first_aid_risk", c);
        }
    });
}
function onChange50dipRisk(c, a) {
    var b = $("#50dip_risk").data("50dip_risk");
    $.post("manage/department.php", {
        op_type: b == "" ? "add_product_unit_custom_category" : "edit_product_unit_custom_category",
        pu_id: c,
        id_pu_ccat: b,
        ccat_id: a
    }, function(d) {
        if (d > 0 && b == "") {
            $("#50dip_risk").data("50dip_risk", d);
        }
    });
}
function onChangeAtecoSector(b) {
    var a = $('select[name="ateco_id"]').data("pu_ateco_id");
    var c = $('select[name="ateco_id"]').val();
    if (c == 0) {
        alert("Seleziona un settore Ateco");
        return false;
    }
    $.post("manage/department.php", {
        op_type: a == "" ? "add_product_unit_ateco" : "edit_product_unit_ateco",
        pu_id: b,
        id_pu_ateco: a,
        ateco_id: c
    }, function(d) {
        if (d > 0 && a == "") {
            $('select[name="ateco_id"]').data("pu_ateco_id", d);
        }
    });
}
function onChangeAtecoRisk(c) {
    var a = $('select[name="ateco_risk_id"]').data("pu_ateco_risk_id");
    var b = $('select[name="ateco_risk_id"]').val();
    if (b == 0) {
        alert("Seleziona un livello di rischio Ateco");
        return false;
    }
    $.post("manage/department.php", {
        op_type: a == "" ? "add_product_unit_ateco_risk" : "edit_product_unit_ateco_risk",
        pu_id: c,
        id_pu_ateco: a,
        ateco_risk_id: b
    }, function(d) {
        if (d > 0 && a == "") {
            $('select[name="ateco_risk_id"]').data("pu_ateco_risk_id", d);
        }
    });
}
function addUserCourse(c, b) {
    var a = $('#add-executed-course input[name="tutor"]').val();
    var d = 0;
    $("#tutors_list option").each(function() {
        if (a == $(this).val()) {
            d = $(this).data("tutor_id");
        }
    });
    if (d == 0) {
        $.post("manage/safety.php", {
            op_type: "add_tutor",
            desc_tutor: a,
            company_id: c
        }, function(e) {
            $.post("manage/safety.php", {
                op_type: "add_user_learning_need",
                user_id: b,
                learning_need_id: $('#add-executed-course select[name="learning_need_id"]').val(),
                execution_date: $('#add-executed-course input[name="execution_date"]').val(),
                tutor_id: e
            }, function(f) {
                if (f) {
                    $("#employee-detail").load("dipendente-dettaglio.php", {
                        user_id: b
                    });
                } else {
                    alert("Non è stato possibile aggiungere il corso.");
                }
                $("#addExecutedCourseModal").modal("hide");
            });
        });
    } else {
        $.post("manage/safety.php", {
            op_type: "add_user_learning_need",
            user_id: b,
            learning_need_id: $('#add-executed-course select[name="learning_need_id"]').val(),
            execution_date: $('#add-executed-course input[name="execution_date"]').val(),
            tutor_id: d
        }, function(e) {
            if (e) {
                $("#employee-detail").load("dipendente-dettaglio.php", {
                    user_id: b
                })
            } else {
                alert("Non è stato possibile aggiungere il corso.");
            }
            $("#addExecutedCourseModal").modal("hide");
        });
    }
}
function employeeDetailDeleteAssignation(b, a) {
    $.post("manage/department.php", {
        op_type: "delete_employee",
        id_dep_empl: b
    }, function(c) {
        if (c > 0) {
            $("#single_user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>');
            $("#single_user").load("dipendente-dettaglio.php", {
                user_id: a
            });
        }
    });
}
function employeeDetailDismissEmployee(c, b, a) {
    $.post("manage/department.php", {
        op_type: "dismiss_employee",
        id_dep_empl: c,
        dismissal_date: b
    }, function(d) {
        if (d > 0) {
            $("#single_user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>');
            $("#single_user").load("dipendente-dettaglio.php", {
                user_id: a
            });
        }
    });
}
function subscribeStep2(b, a) {
    $("#home-container").html('<img src="img/preloader-snake-blue.gif"/>').load("pages/subscribe.php?learning_project_id=" + a, {
        users: b
    });
}
function updateNota(b, a) {
    var c = $("#" + a + b).val();
    $.post("manage/purchase.php", {
        op_type: "update_nota",
        id: b,
        nota: c
    }, function(d) {
        if (d == 0) {
            alert("Aggiornamento non riuscito");
        } else {
            if (a == "d_nota_") {
                $("#c_nota_" + b).val(c);
            } else {
                $("#d_nota_" + b).val(c);
            }
            alert("Aggiornamento effettuato");
        }
    });
}
function disableLearningOjb(b) {
    var a = false;
    $.ajax({
        type: "POST",
        async: false,
        url: "manage/learning_object.php",
        data: {
            op_type: "disable_it",
            id: b
        },
        success: function(c) {
            if (c > 0) {
                a = true;
            }
        }
    });
    return a;
}
function enableLearningOjb(b) {
    var a = false;
    $.ajax({
        type: "POST",
        async: false,
        url: "manage/learning_object.php",
        data: {
            op_type: "enable_it",
            id: b
        },
        success: function(c) {
            if (c > 0) {
                a = true;
            }
        }
    });
    return a;
}

/* ************** ELIMINA LICENZA E ACQUISTO COLLEGATO ****************** */
function removeLicenceAndPurchase(licence_id){
    var res;
    if (confirm("Hai scelto di eliminare una licenza. ATTENTO! Questa operazione non è reversibile, sei certo di voler procedere?")) {
        
        $.ajax({
            type: "POST",
            async: false,
            url: "manage/license.php", 
            data: {
                op_type: "remove_licence_purchase", 
                licence_id: licence_id
            },
            success: function (data) {
                if (data === "SUCCESS") {
                    alert("La licenza è stata cancellata.");
                    res = true;
//                } else if (data === "SUCCESS INVOICED") {
//                    alert("La licenza è stata cancellata. L'acquisto è già stato fatturato pertanto non è stato possibile eliminarlo.");
//                    res = true;
                } else {
                    alert("Errore nel tentativo di cancellare la licenza: " + data);
                    res = false;
                }
            }
        });
        return res;
    }
}


$(function() {
    $('[data-toggle="tooltip"]').tooltip({
        content: function() {
            return $(this).prop("title");
        }
    });
    $('[data-toggle="popover"]').popover();
    $("body").on("hidden.bs.modal", "#simpleLargeModal", function() {
        $("#simpleLargeModal .modal-content").empty();
    });
    $("body").on("click", '.row-selectable input[type="checkbox"]', function(a) {
        if ($(this)[0].checked) {
            $(this).parents("tr").addClass("selected");
        } else {
            $(this).parents("tr").removeClass("selected");
        }
    });
    $("body").on("click", ".single-row-selectable tbody > tr", function(a) {
        if (!$(this).hasClass("selected")) {
            $(this).addClass("selected").siblings().removeClass("selected");
        }
    });
    $("body").on("click", "li.disabled > a", function(a) {
        a.preventDefault();
        a.stopPropagation();
    });
    $("body").on("mouseover", ".a-image", function() {
        var a = $(this).data("image-mouseover");
        $(this).children("img").attr("src", a);
    }).mouseout(function() {
        var a = $(this).data("image-mouseout");
        $(this).children("img").attr("src", a);
    });
    fitHomeAccordionPanelCollpase();
    $(window).resize(function() {
        fitHomeAccordionPanelCollpase();
    });
    $("body").on("click", "#purchases .export-selected", function() {
        var a = $(".table-purchases thead th").map(function() {
            return $(this).text();
        });
        var b = $("#purchases-admin .table-purchases td.select > input:checked").parents("tr");
        var c = b.map(function() {
            var f = new Object();
            for (var d = 0; d < a.length - 1; d++) {
                var e = $(this).find("td:eq(" + d + ")");
                if (e.children().is("input")) {
                    f[a[d]] = e.children().val();
                } else {
                    f[a[d]] = e.text();
                }
            }
            return f;
        }).get();
        if (c.length > 0) {
            downloadCSV({
                filename: "acquisti.csv",
                data: convertArrayOfObjectsToCSV({
                    data: c
                })
            });
        }
    });
    
});