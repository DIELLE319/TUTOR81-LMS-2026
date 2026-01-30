$(document).ready(function() {
    var e;
    var IVA = 0.22;
    var customerCompanyId;
    var f;
    var d = function(h, i, j) {
        h.each(function() {
            var n = $(this);
            var l = n.find(".amountRange").html().trim().split("-");
            var m = parseInt(l[0]);
            var k = parseInt(l[1]);
            if (i >= m && i <= k) {
                j = n.find(".amountPrice").html().substring(2).trim();
            }
        });
        return j
    };
    var c = function(i, h) {
        console.log("Enter in setInviaLicenzeRow");
        e = i.parent("td").parent("tr").next(".inviaLicenzeRow").find(".sendLiceneceRowsContainer");
        console.log(e);
        if (h == 1) {
            var j = $(e.find(".inviaRows")[0]).clone(true, true);
            console.log(j);
            j.find(".inviaRows").attr("value", 3);
            j.find("input[type='text']").val("");
            /*j.find("input.datepickers").removeClass("hasDatepicker").removeData("datepicker").unbind().datepicker({
                autoclose: true,
                beforeShow: function() {
                    setTimeout(function() {
                        $(".ui-datepicker").css("z-index", 99999999999999)
                    }, 0)
                }
            });*/
            e.append(j);
            e.find('.inviaRows').last().find("input.datepicker").removeClass("hasDatepicker").removeData("datepicker").unbind().datepicker({
                autoclose: true,
                format: 'dd/mm/yyyy',
                language: 'it',
                beforeShow: function() {
                    setTimeout(function() {
                        $(".ui-datepicker").css("z-index", 99999999999999);
                    }, 0)
                }
            });
            e.find('.inviaRows').last().find(".start_date").datepicker().datepicker("setDate", new Date());
            e.find('.inviaRows').last().find(".end_date").datepicker().datepicker("setDate", new Date(new Date().setMonth(new Date().getMonth() + 3)));
            
            //j.find(".start_date").datepicker().datepicker("setDate", new Date());
            //j.find(".end_date").datepicker().datepicker("setDate", new Date(new Date().setFullYear(new Date().getFullYear() + 1)));
        } else {
            if (h == -1) {
                e.find(".inviaRows").last().remove();
            }
        }
    };
    $(".checkout-vendi").click(function() {
        $(".tab-content").css({
            "background-color": "#394263",
            color: "#fff"
        });
        $(".wizard-steps .col-sm-4 div").removeClass("active");
        $(".wizard-steps").find("a").css("color", "#000");
        var i = $("#nuovoUtenteTab");
        i.addClass("active");
        i.find("a").css("color", "#fff");
        $(".tab-content .tab-pane").removeClass("active");
        var j = $("#nuovoUtente");
        j.addClass("active");
        console.log($(this).attr("id"));
        var k = $("#invia_Licenze_Modal").modal();
        var m = $(this).siblings(".rowLearningProjectID").val();
        console.log("rowLearningProjectID: " + m);
        var h = k.find(".learningProjectID").val(m);
        console.log(h.val());
        var l = $("#ecom-orders").find('.rowLearningProjectID[value="' + m + '"]').parent("td").parent("tr");
        console.log(l);
        f = $("#modalSellTitle").text(l.find(".courseTitle").text());
        console.log(f);
    });
    $(".datepickers").datepicker({
        autoclose: true,
        beforeShow: function() {
            setTimeout(function() {
                $(".ui-datepicker").css("z-index", 99999999999999);
            }, 0)
        }
    });
    $(".start_date").datepicker().datepicker("setDate", new Date());
    $(".end_date").datepicker().datepicker("setDate", new Date(new Date().setMonth(new Date().getMonth() + 3)));
    
    if ($(".initialQuantita").val() == 1) {
        $(".decQuantita").prop("disabled", true);
        $(".licenseInfo").css("display", "none");
        $(".licenseInfoVertical").css("display", "");
        $(".licenseInfoVerticalAbs").css("display", "");
        $(".inviaRows div:first-child").removeClass("email-field");
        $(".inviaRows div:nth-child(2)").removeClass("startEndDate-field");
        $(".inviaRows div:nth-child(3)").removeClass("startEndDate-field");
        $(".inviaRows div:nth-child(4)").removeClass("alert-field");
        $(".inviaRows div:nth-child(5)").removeClass("info-optional-field-cognome");
        $(".inviaRows div:nth-child(6)").removeClass("info-optional-field");
        $(".inviaRows div:nth-child(7)").removeClass("info-optional-field");
        $(".inviaRows div:nth-child(8)").removeClass("users-field");
        $(".inviaRows div:nth-child(9)").removeClass("info-optional-field");
        $(".inviaRows .form-group label").removeClass("hidden");
        $(".inviaRows .form-group input").removeClass("license-row-input");
        $(".inviaRows .needed-fields").addClass("verticalRowWidth");
        $(".inviaRows .optional-fields").addClass("verticalRowWidth");
        $(".inviaRows div:nth-child(5)").addClass("position-abs-cognome");
        $(".inviaRows div:nth-child(6)").addClass("position-abs-nome");
        $(".inviaRows div:nth-child(7)").addClass("position-abs-tax-code");
        $(".inviaRows div:nth-child(8)").addClass("position-abs-utente");
        $(".inviaRows div:nth-child(9)").addClass("position-abs-accreditation_code");
        $(".inviaRows .form-control").css("width", "50%");
        $(".inviaRows").removeClass("form-group-optional");
        $(".inviaRows .license-email-icon").css("display", "");
        $(".inviaRows .license-start-date-icon").css("display", "");
        $(".inviaRows .license-end-date-icon").css("display", "");
        $(".inviaRows .license-alert-icon").css("display", "");
        $(".inviaRows").css("padding-top", "10px");
    }
    $(".dec").on("click", function() {
        var m = $(this).parent("td").find(".productQuantita");
        var i = $(this).parent("td").find(".dec");
        var h = $(this).parent("td").parent("tr").find(".productPriceListRow");
        var k = $(this).parent("td").parent("tr").find(".productPrice").text();
        var l = $(this).parent("td").parent("tr").find(".tuoCosto");
        var j = parseInt(m.val()) - 1;
        if (j == 1) {
            i.prop("disabled", true);
            $(".licenseInfo").css("display", "none");
            $(".licenseInfoVerticalAbs").css("display", "");
            $(".inviaRows").css({
                display: "",
                "padding-top": "10px"
            });
            $(".inviaRows .needed-fields").addClass("verticalRowWidth");
            $(".inviaRows .optional-fields").addClass("verticalRowWidth");
            $(".inviaRows div:nth-child(5)").addClass("position-abs-cognome");
            $(".inviaRows div:nth-child(6)").addClass("position-abs-nome");
            $(".inviaRows div:nth-child(7)").addClass("position-abs-tax-code");
            $(".inviaRows div:nth-child(8)").addClass("position-abs-utente");
            $(".inviaRows div:nth-child(9)").addClass("position-abs-accreditation_code");
            $(".inviaRows div:first-child").removeClass("email-field");
            $(".inviaRows div:nth-child(2)").removeClass("startEndDate-field");
            $(".inviaRows div:nth-child(3)").removeClass("startEndDate-field");
            $(".inviaRows div:nth-child(4)").removeClass("alert-field");
            $(".inviaRows div:nth-child(5)").removeClass("info-optional-field-cognome");
            $(".inviaRows div:nth-child(6)").removeClass("info-optional-field");
            $(".inviaRows div:nth-child(7)").removeClass("info-optional-field");
            $(".inviaRows div:nth-child(8)").removeClass("users-field");
            $(".inviaRows div:nth-child(9)").removeClass("info-optional-field");
            $(".inviaRows .form-group label").removeClass("hidden");
            $(".inviaRows .form-group input").removeClass("license-row-input");
            $(".inviaRows .license-email-icon").css("display", "");
            $(".inviaRows .license-start-date-icon").css("display", "");
            $(".inviaRows .license-end-date-icon").css("display", "");
            $(".inviaRows .license-alert-icon").css("display", "");
            $(".inviaRows .form-control").css("width", "50%");
            $(".inviaRows").removeClass("form-group-optional");
        }
        m.val(j);
        k = d(h, j, k);
        l.html("&euro; " + String(k).replace(".", ","));
        c($(this), -1);
    });
    $(".inc").on("click", function() {
        $(".licenseInfo").css("display", "inline-flex");
        $(".licenseInfoVerticalAbs").css("display", "none");
        $(".inviaRows").css({
            display: "inline-flex",
            "padding-top": "0"
        });
        $(".inviaRows .form-group").removeClass("verticalRowWidth");
        $(".inviaRows div:first-child").addClass("email-field");
        $(".inviaRows div:nth-child(2)").addClass("startEndDate-field");
        $(".inviaRows div:nth-child(3)").addClass("startEndDate-field");
        $(".inviaRows div:nth-child(4)").addClass("alert-field");
        $(".inviaRows div:nth-child(5)").addClass("info-optional-field-cognome");
        $(".inviaRows div:nth-child(6)").addClass("info-optional-field");
        $(".inviaRows div:nth-child(7)").addClass("info-optional-field");
        $(".inviaRows div:nth-child(8)").addClass("users-field");
        $(".inviaRows div:nth-child(9)").addClass("info-optional-field");
        $(".inviaRows .form-control").removeAttr("style");
        $(".inviaRows div:nth-child(5)").removeClass("position-abs-cognome");
        $(".inviaRows div:nth-child(6)").removeClass("position-abs-nome");
        $(".inviaRows div:nth-child(7)").removeClass("position-abs-tax-code");
        $(".inviaRows div:nth-child(8)").removeClass("position-abs-utente");
        $(".inviaRows div:nth-child(9)").removeClass("position-abs-accreditation_code");
        $(".inviaRows .form-group label").addClass("hidden");
        $(".inviaRows .form-group input").addClass("license-row-input");
        $(".inviaRows .license-email-icon").css("display", "none");
        $(".inviaRows .license-start-date-icon").css("display", "none");
        $(".inviaRows .license-end-date-icon").css("display", "none");
        $(".inviaRows .license-alert-icon").css("display", "none");
        $(".inviaRows").addClass("form-group-optional");
        var m = $(this).parent("td").find(".productQuantita");
        var i = $(this).parent("td").find(".dec");
        var h = $(this).parent("td").parent("tr").find(".productPriceListRow");
        var k = $(this).parent("td").parent("tr").find(".productPrice").text();
        var l = $(this).parent("td").parent("tr").find(".tuoCosto");
        var j = parseInt(m.val()) + 1;
        m.val(j);
        i.prop("disabled", false);
        k = d(h, j, k);
        l.html("&euro; " + String(k).replace(".", ","));
        c($(this), 1);
    });
    $(".productQuantita").on("change", function() {
        console.log("Enter in productQuantitaChange");
        var l = $(this).parent("td").find(".productQuantita");
        var h = $(this).parent("td").parent("tr").find(".productPriceListRow");
        var j = $(this).parent("td").parent("tr").find(".productPrice").text();
        var k = $(this).parent("td").parent("tr").find(".tuoCosto");
        var i = parseInt(l.val());
        j = d(h, i, j);
        k.html("&euro; " + String(j).replace(".", ","));
    });
    $(".alert_dec").unbind("click");
    $(".alert_dec").click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        var i = $(this).parent("div").children(".alert_dec");
        var j = $(this);
        var h = parseInt(j.siblings(".alert_days_amount").val()) - 1;
        if (h == 1) {
            i.prop("disabled", true);
        }
        j.siblings(".alert_days_amount").val(h);
    });
    $(".alert_inc").unbind("click");
    $(".alert_inc").click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        var i = $(this);
        var h = parseInt(i.siblings(".alert_days_amount").val()) + 1;
        $(".alert_dec").prop("disabled", false);
        i.siblings(".alert_days_amount").val(h);
    });
    
    /* acquisto licenze corsi in backend piattaforma 
     * 
    */
    $(".sell").on("click", function() {
        $.isLoading({
            text: "Attendere prego ..."
        });
        customerCompanyId = $("#clienteCompanyID").val();
        // check utente selezionato
        if (customerCompanyId == 0) {
            alert('Seleziona il cliente!');
            $.isLoading("hide");
            return false;
        }
        var wizard_step = $(this).parentsUntil(".modal-body").find(".wizard-steps").find(".active");
        var employee_selected_row;              // righe utenti nuovi o selezionati
        var selected_employees = Array();    // array di id utenti esistenti
        var new_employees = Array();            // array dati nuovi utenti
        var amount;
        var for_existing_users = false;
        if (for_existing_users = (wizard_step.attr("id") != "nuovoUtenteTab")) {
            // check utenti esistenti selezionati
            employee_selected_row = employeesSellTable.rows('.selected').nodes();
            amount = employee_selected_row.length;
            if (amount == 0) {
                alert('Seleziona almeno un utente!');
                $.isLoading("hide");
                return false;
            }
            employee_selected_row.each(function(value, index){
                selected_employees[index] = {
                    id                  : $(value).find('.user_id').text(),
                    name                : $(value).find('.name').text(),
                    surname             : $(value).find('.surname').text(),
                    tax_code            : $(value).find('.tax_code').text(),
                    email               : $(value).find('.email').text(),
                    accreditation_code  : $(value).find('.accreditation_code').val()
                };
            });
        } else {
            // check dati nuovi utenti completi
            employee_selected_row = $("#sendLicenceForm").find(".sendLiceneceRowsContainer .inviaRows");
            var missing_data = false;
            employee_selected_row.each(function(index) {
                var employee_row = $(this);
                new_employees[index] = {
                    email               : employee_row.find('input.email').val(),
                    surname             : employee_row.find('input.cognome').val(),
                    name                : employee_row.find('input.nome').val(),
                    tax_code            : employee_row.find('input.cod_fisc').val()
                }
                
                employee_row.find('.has-error').removeClass('has-error')
                if (new_employees[index].email == '' || !validateEmail(new_employees[index].email)) {
                    employee_row.find(".email").parent().addClass('has-error');
                    missing_data = true;
                }
                if (new_employees[index].tax_code != '' || new_employees[index].surname != "" || new_employees[index].name != "") {
                    if (new_employees[index].surname == "") {
                        employee_row.find('.cognome').parent().addClass('has-error');
                        missing_data = true;
                    }
                    if (new_employees[index].name == "") {
                        employee_row.find('.nome').parent().addClass('has-error');
                        missing_data = true;
                    }
                    if (!controllaCF(new_employees[index].tax_code)) {
                        employee_row.find('.cod_fisc').parent().addClass('has-error');
                        missing_data = true;
                    }
                }
                if (!missing_data) {
                    new_employees[index].func_id            = employee_row.find(".tipo_utente").val();
                    new_employees[index].start_date         = employee_row.find(".start_date").datepicker('getDate').toISOString();
                    new_employees[index].end_date           = employee_row.find(".end_date").datepicker('getDate').toISOString();
                    new_employees[index].alert_days         = employee_row.find(".alert_days").val();
                    new_employees[index].accreditation_code = employee_row.find(".accreditation_code").val(); 
                }
            });
            if (missing_data) {
                alert("Completate e controllate tutti i campi obbligatori segnalati in rosso.");
                $.isLoading("hide");
                return false;
            }
            
            amount = employee_selected_row.length;//ecom_licences.find(".productQuantita").val();          
        }
        
        var ecom_licences = $("#ecom-licences");
        var course_id = ecom_licences.find(".learningProjectID").val();
        var selected_course = $("#ecom-orders tr").find('.rowLearningProjectID[value="' + course_id + '"]').parent("td").parent("tr");
        var admin_id = $("#userID").val();
        var tutor_company_id = $("#tutorCompanyID").val();
        var tutor_company_email = $("#tutorEmail").val();
        var user_company_ref = 0;
        var learning_project_id = selected_course.find(".productId").text();
        var cost_centre_id = 0;
        var price = selected_course.find(".tuoCosto").text();//selected_course.find(".productPrice").text();
        price = Number(price.replace(',','.').replace(/[^0-9\.-]+/g,""));
        var payment_type = "backend";
        //var sell_button = $(this);
        
        //Inizio generazione della nuova vendita e delle licenze           
        $.post("/ecommerce/license.php", {
            op_type: "new_backend_purchase",
            for_existing_users: for_existing_users,
            tutor_id: admin_id,
            amount: amount,
            course_id: course_id,
            learningproject_id: learning_project_id,
            customercompany_id: customerCompanyId,
            usercompany_ref: user_company_ref,
            payment: payment_type,
            price: price,
            costcentre_id: cost_centre_id,
            destination_email: tutor_company_email,
            employees: JSON.stringify(for_existing_users ? selected_employees : new_employees)
        });
        alert("Controlla la tua posta elettronica dove ti abbiamo inviato la conferma del'acquisto.");
        location.href= 'bk-sold-courses.php?scelta=venduti';
        return false;
    });
    
    /**
     * check validation email on focus out
     */
    $(".email").bind("change", function() {
        var j = $(this).val();
        var i = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        if (!i.test(j)) {
            $(this).parent().addClass('has-error');
            alert("Inserisci una email valida.");
            //h.preventDefault();
        } else {
            $(this).parent().removeClass('has-error');
        }
    });
    
    /**
     * check validation tax code on focus out
     */
    $(".cod_fisc").bind("change", function() {
        var tax_code = $(this).val();
        var nome = $(this).parents('.inviaRows').find('.nome').val();
        var cognome = $(this).parents('.inviaRows').find('.cognome').val();
        var correct = false;
        if (tax_code == '' ) {
            if (cognome != '' || nome != '') {
                alert("Il codice fiscale non può essere vuoto.");
            } else {
                correct = true;
            }
        } else {
            if (!controllaCF(tax_code)) {
                alert ("Il codice fiscale non è corretto.");
            } else if (taxcodeExist(tax_code)) {
                alert ("Questo codice fiscale è già registrato in piattaforma.");
            } else {
                correct = true;
            }
        }
        if (correct) $(this).parent().removeClass('has-error');
        else $(this).parent().addClass('has-error');
    });
    
    $(".select-type-unita li a").click(function() {
        $("#unita:first-child").text($(this).text());
        $("#unita:first-child").val($(this).text());
    });
    $(".select-type-reparto li a").click(function() {
        $("#reparto:first-child").text($(this).text());
        $("#reparto:first-child").val($(this).text());
    });
    $(".select-type-utente li a").click(function() {
        $("#utente:first-child").text($(this).text());
        $("#utente:first-child").val($(this).text());
    });
        
    $(".blockedKey").on("click", function() {
        $.isLoading({
            text: "Attendere prego ..."
        });
        if ($(this).parent("td").prev("td").children("span.blocked").css("display") != "none") {
            $(this).parent("td").prev("td").children("span.sold").html(" Sblocatto").show().siblings("span").hide();
            $(this).parent("td").children("a.write").show().siblings("a").hide();
            $(this).parent("td").children("button.blockedKey").html(" Sblocca").hide();
        }
        console.log("sblocca licenza inviando mail del corso e assegnata dopo iscrizione del corsista");
        console.log($(this).parent("td").parent("tr").find(".lpuid").val());
        $.post("/ecommerce/bk/unlock_licence.php", {
            lpu_id: $(this).parent("td").parent("tr").find(".lpuid").val()
        }).done(function(h) {
            console.log(h);
            console.log("Inviata iscrizione al corsro....");
            location.reload(true);
            $.isLoading("hide");
        })
    });
    $("#search-employee .search-query").autocomplete({
        source: "lib/employee_search.php?option=&istutor=" + $("#isTutor").val() + "&companyid=" + $("#companyID").val(),
        minLength: 2,
        select: function(i, j) {
            console.log("ritorno search ....");
            console.log(j);
            var h = j.item.id;
            if (h > 0) {
                $("#single-user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>').load("pages/sections/employee-detail.php", {
                    user_id: h
                });
                $("#cerca-utenti-modal").modal();
            }
        },
        html: true,
        open: function(h, i) {
            $(".ui-autocomplete").css("z-index", 1000);
        }
    });
    $("body").on("hidden.bs.modal", ".modal", function() {
        $("#search-employee").find("input[type='text']").val("");
        $("#clienteCompanyID option:selected").prop("selected", false);
        $("#clienteCompanyID option:first").prop("selected", "selected");
        var h = $(".productQuantita").val("1");
        var i = $(".dec");
        i.prop("disabled", true);
        $(".inviaRows").prevUntil(e).remove();
        if (h.val() == 1) {
            i.prop("disabled", true);
            $(".licenseInfo").css("display", "none");
            $(".licenseInfoVerticalAbs").css("display", "");
            $(".inviaRows").css({
                display: "",
                "padding-top": "10px"
            });
            $(".inviaRows .form-group").addClass("verticalRowWidth").removeClass('has-error');
            $(".inviaRows div:first-child").removeClass("email-field");
            $(".inviaRows div:nth-child(2)").removeClass("startEndDate-field");
            $(".inviaRows div:nth-child(3)").removeClass("startEndDate-field");
            $(".inviaRows div:nth-child(4)").removeClass("alert-field");
            $(".inviaRows div:nth-child(5)").removeClass("info-optional-field-cognome");
            $(".inviaRows div:nth-child(6)").removeClass("info-optional-field");
            $(".inviaRows div:nth-child(7)").removeClass("info-optional-field");
            $(".inviaRows div:nth-child(8)").removeClass("users-field");
            $(".inviaRows div:nth-child(5)").addClass("position-abs-cognome");
            $(".inviaRows div:nth-child(6)").addClass("position-abs-nome");
            $(".inviaRows div:nth-child(7)").addClass("position-abs-tax-code");
            $(".inviaRows div:nth-child(8)").addClass("position-abs-utente");
            $(".inviaRows .form-group label").removeClass("hidden");
            $(".inviaRows .form-group input").removeClass("license-row-input");
            $(".inviaRows .license-email-icon").css("display", "");
            $(".inviaRows .license-start-date-icon").css("display", "");
            $(".inviaRows .license-end-date-icon").css("display", "");
            $(".inviaRows .license-alert-icon").css("display", "");
            $(".inviaRows .form-control").css("width", "50%");
            $(".inviaRows").removeClass("form-group-optional");
        }
        $(".inviaRows").find("input[type='email'], input[type='text']").val("");
        $(".inviaRows").find(".email").css("border-color", "#dbe1e8");
        $(".start_date").datepicker().datepicker("setDate", new Date());
        $(".end_date").datepicker().datepicker("setDate", new Date(new Date().setFullYear(new Date().getFullYear() + 1)));
        $(this).find(".alert_days_amount").val("15");
        $(this).find("#tipoUtente select").first().val("0");
    });
    $(".wizard-steps a").click(function(i) {
        $(".wizard-steps .col-sm-4 div").removeClass("active");
        $(".wizard-steps").find("a").css("color", "#000");
        var h = $(this).parent().parent();
        if (!h.hasClass("active")) {
            h.addClass("active");
            h.find("a").css("color", "#fff");
            h.find("a").css("color", "#fff");
            $("#ecom-licences").find(".email").css("border-color", "#dbe1e8");
        }
        i.preventDefault()
    });
    $("#utenteEsistenteTab").click(function() {
        $(".tab-content").css({
            "background-color": "#fff",
            color: "#000"
        })
    });
    $("#nuovoUtenteTab").click(function() {
        $(".tab-content").css({
            "background-color": "#394263",
            color: "#fff"
        })
    });
    $(".pu_select").on("change", function() {
        var h = $(this).val();
        a(h, 0);
    });
    var a = function(o, j) {
        console.log("------- Chiamata alla refresh Table...");
        var h = $("#clienteCompanyID").val();
        console.log("Company id nella refresh: " + h);
        var i = $("#ecom-licences");
        var n = i.find(".learningProjectID").val();
        var k = $("#ecom-orders tr").find('.rowLearningProjectID[value="' + n + '"]').parent("td").parent("tr");
        console.log("CourseID: " + n);
        var m = k.find(".productId").text();
        console.log("Learning project ID: " + m);
        employeesSellTable.clear().draw();
        $.get("lib/employee_search.php?option=employeesfree&term=&learningprojectid=" + m + "&companyid=" + h + "&unita=" + o + "&reparto=" + j, function(q) {
            console.log(q);
            console.log("Ritornata nuova lista utenti:");
            var p = $.parseJSON(q);
            console.log(p);
            employeesSellTable.rows.add(p);
            employeesSellTable.draw();
        });
    };
    $("#utenteEsistenteTabLink").on("click", function() {
        $("select[name=department]").addClass("hidden");
        var h = $("#clienteCompanyID").val();
        //console.log("Company id: " + h);
        $(".product_unit > div.dep_controls").empty();
        $.post("manage/department.php", {
            op_type: "get_pu",
            pu_id: h
        }, function(k) {
            var j = '<option value="0">Seleziona unità</option>';
            k = $.parseJSON(k);
            if (k == 0) {
                $(".product_unit > div.pu_controls select").empty().append(j);
            } else {
                $.each(k, function(l, m) {
                    j += '<option value="' + m.id_pu + '">' + m.short_desc_pu + "</option>";
                    if (l == Object.keys(k).length - 1) {
                        $(".product_unit > div.pu_controls select").empty().append(j);
                    }
                })
            }
            var i = $(".pu_select option").length;
            //console.log("LengthAfterPost " + i);
            if (i > 1) {
                $(".pu_select").removeClass("hidden");
                $("#employees-sell-table").DataTable().clear().draw();
            } else {
                $(".pu_select").addClass("hidden");
            }
            a(0, 0);
        })
    });
    $("#clienteCompanyID").on("change", function() {
        //console.log("Change company...");
        $("select[name=department]").addClass("hidden");
        var j = $("#utenteEsistenteTab").attr("class");
        //console.log("Change...");
        //console.log(j);
        if (j == "active") {
            //console.log("Active...");
            var h = $("#clienteCompanyID").val();
            //console.log("Company ids: " + h);
            var i = $("#employees-sell-table").DataTable();
            $(".product_unit > div.dep_controls").empty();
            $.post("manage/department.php", {
                op_type: "get_pu",
                pu_id: h
            }, function(m) {
                var l = '<option value="0">Seleziona unità</option>';
                m = $.parseJSON(m);
                if (m == 0) {
                    $(".product_unit > div.pu_controls select").empty().append(l);
                } else {
                    $.each(m, function(n, o) {
                        l += '<option value="' + o.id_pu + '">' + o.short_desc_pu + "</option>";
                        if (n == Object.keys(m).length - 1) {
                            $(".product_unit > div.pu_controls select").empty().append(l);
                        }
                    })
                }
                var k = $(".pu_select option").length;
                //console.log("LengthAfterPost " + k);
                if (k > 1) {
                    $(".pu_select").removeClass("hidden");
                    $("#employees-sell-table").DataTable().clear().draw();
                } else {
                    $(".pu_select").addClass("hidden");
                    a(0, 0);
                }
            })
        }
    });
    $('select[name="product_unit"]').on("change", function() {
        //console.log("Product unit id: " + $('select[name="product_unit"]').val());
        if ($('select[name="product_unit"]').val() == 0) {
            $(".departments > div.dep_controls").empty();
        } else {
            $.post("manage/department.php", {
                op_type: "get_pu_departments",
                pu_id: $('select[name="product_unit"]').val()
            }, function(i) {
                var h = '<select title="Reparto" class="form-control" size="1" name="department" ><option value="0">Seleziona reparto</option>';
                i = $.parseJSON(i);
                if (i == 0) {
                    $(".departments > div.dep_controls").empty().append(h + "</select>");
                } else {
                    $.each(i, function(j, k) {
                        h += '<option value="' + k.id_dep + '">' + k.short_desc_dep_type + "</option>";
                        if (j == Object.keys(i).length - 1) {
                            $(".departments > div.dep_controls").empty().append(h + "</select>");
                        }
                    });
                    $("select[name=department]").on("change", function() {
                        var k = $('select[name="product_unit"]').val();
                        var j = $(this).val();
                        a(k, j);
                    })
                }
            })
        }
    });
    $("#masterCheckbox").click(function() {
        $("input:checkbox").not(this).prop("checked", this.checked);
    })
});

