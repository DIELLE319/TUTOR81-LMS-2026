$(document).ready(function() {
    var host_name = window.location.host;
    var p;
    var w = $("#checkout-pagamento");
    var J = 0.22;
    var a;
    var i;
    var y = $("#checkoutEmailSpedizione");
    var vatCode = $("#checkoutVatCode");
    var l = $("#checkoutEmailLabel");
    var f = $(".checkoutId");
    var G = $(".checkoutTitle");
    var B = $(".checkoutTipo");
    var g = $(".checkoutOre");
    var j = $(".checkoutImage");
    var D = $(".checkoutQuantita");
    var b = $(".checkoutPrezzo");
    var I = $(".checkoutVatPrezzo");
    var z = $(".checkoutSubTotale");
    var e = $(".checkoutTotSubTotale");
    var r = $("input:radio[name=checkout-payments]");
    var A = $(".checkoutTotale");
    var tutorCompanyID = $("#tutorCompanyID");
    var u = $("#tutorAdminID");
    var m = function() {
        $("#paypal").hide();
        $("#bonifico").show();
        r[0].checked = true;
    };
    var h = function() {
        $("#bonifico").hide();
        $("#paypal").show();
        r[1].checked = true;
    };
    var d = function() {
        return z.val().replace(",", ".") * J;
    };
    var x = function() {
        return b.val().replace(",", ".") * D.val();
    };
    var o = function() {
        return d() + x();
    };
    var H = function() {
        z.each(function() {
            var K = parseFloat(x()).toFixed(2);
            $(this).val(String(K).replace(".", ","));
        });
        e.each(function() {
            var K = parseFloat(x()).toFixed(2);
            $(this).val(String(K).replace(".", ","));
        });
        I.each(function() {
            var K = parseFloat(d()).toFixed(2);
            $(this).val(String(K).replace(".", ","));
        });
        A.each(function() {
            var K = parseFloat(o()).toFixed(2);
            $(this).val(String(K).replace(".", ","));
        })
    };
    var C = function(K) {
        D.each(function() {
            $(this).val(K);
        })
    };
    var v = function(K) {
        b.each(function() {
            $(this).val(String(K).replace(".", ","));
        })
    };
    var q = function() {
        var K = parseInt(D.val());
        i.each(function() {
            var O = $(this);
            var M = O.find(".amountRange").html().trim().split("-");
            var N = parseInt(M[0]);
            var L = parseInt(M[1]);
            if (K >= N && K <= L) {
                console.log(M);
                var P = O.find(".amountPrice").html().substring(2).trim();
                console.log(P);
                v(P);
            }
        })
    };
    var checkoutWizard = $("#checkout-wizard");
    checkoutWizard.formwizard({
        disableUIStyles: true,
        inDuration: 0,
        outDuration: 0,
        textBack: "Indietro",
        textNext: "Prossimo",
        textSubmit: "Acquista"
    });
    function checkCompanyVat(vat_code){
        var company = false;
        $.ajax({
            async: false,
            url: '/ecommerce/company.php',
            method: 'POST', 
            data: 
                {
                    op_type: 'get_company_by_vat_code',
                    vat_code: vat_code
                }, 
            success: function(data){
                    if (data != 0) company = JSON.parse(data);
                }
            }
        );
        return company;
    }
    var t = function(K) {
        if (K == "checkout-first") {
            console.log(K);
        } else {
            if (K == "checkout-second") {
                console.log(K);
            } else {
                if (K == "checkout-third") {
                    console.log(K);
                } else {
                    if (K == "checkout-fourth") {
                        console.log(K);
                        l.html(y.val());
                    } else {
                        console.log("No tabIndex");
                    }
                }
            }
        }
    };
    $("#checkout-payment-bonifico").click(function() {
        m();
    });
    $("#checkout-payment-paypal").click(function() {
        h();
    });
    $("#back").on("click", function() {
        checkoutWizard.formwizard("show", "checkout-first");
    });
    checkoutWizard.bind("before_step_shown", function(K, L) {
        t(L.currentStep);
    });
    $(D.get(0)).on("change", function() {
        C($(this).val());
        q();
        H();
    });
    var E = $("#dec");
    E.prop("disabled", true);
    E.click(function() {
        var K = parseInt($(D.get(0)).val()) - 1;
        if (K == 1) {
            E.prop("disabled", true);
        }
        C(K);
        q();
        H();
    });
    var k = $("#inc");
    k.click(function() {
        var K = parseInt($(D.get(0)).val()) + 1;
        C(K);
        E.prop("disabled", false);
        q();
        H();
    });
    $(document).on("click", ".buttonAcquista", function() {
        p = $(this).closest(".store-item");
        var M = p;
        console.log(M);
        if (M.length > 0) {
            var N = M.find(".productId").text();
            console.log(N);
            var Q = M.find(".productTitle").text();
            console.log(Q);
            var L = M.find(".productTipo").text();
            console.log(L);
            var K = M.find(".productOre").text();
            console.log(K);
            var P = M.find(".productImageSrc").attr("src");
            console.log(P);
            var O = M.find(".productPrice").text();
            console.log(O);
            i = M.find(".productPriceListRow");
        } else {
            var N = $(".productId").text();
            console.log(N);
            var Q = $(".productTitle").text();
            console.log(Q);
            var L = $(".productTipo").text();
            console.log(L);
            var K = $(".productOre").text();
            console.log(K);
            var P = $(".productImageSrc").attr("src");
            console.log(P);
            var O = $(".productPrice").text();
            console.log(O);
            i = $(".productPriceListRow");
        }
        console.log(i);
        f.each(function() {
            $(this).val(N);
        });
        G.each(function() {
            $(this).text(Q);
        });
        B.each(function() {
            $(this).text(L);
        });
        g.each(function() {
            $(this).text(K);
        });
        j.each(function() {
            $(this).attr("src", P);
        });
        v(O);
        C(1);
        q();
        H();
        l.html("-- email --");
        y.val("");
        vatCode.val("");
        m();
        checkoutWizard.formwizard("show", "checkout-first");
        w.modal("show");
    });
    $("#checkout").on("click", function(M) {
        console.log("Completa acquisto");
        $(this).prop("disabled", true);
        var Q = u.val();
        var tutor_company_id = tutorCompanyID.val();
        var N = f.val();
        var T = D.val();
        var L = 0;
        var R = o();
        var P = y.val();
        var checkIVA = ControllaPIVA(vatCode.val());
        var business_name = $('#business_name').val();
        var address = $('#address').val();
        var city = $('#city').val();
        var province_id = $('#province_id').val();
        var errorTag;
        var company = {};
        try {
            if (!$('#register-terms').is(':checked')){
                errorTag = $('#register-terms');
                throw("Attenzione! Spunta Termini e condizioni d'uso per la loro accettazione.");
            } else if (P === "") {
                errorTag = y;
                throw("Attenzione manca la destinazione E-Mail dove inviare le licenze!");
            } else if (!validateEmail(P)) {
                errorTag = y;
                throw("Attenzione la destinazione E-Mail non è un indirizzo valido!");
            } else if (checkIVA !== "") {
                errorTag = vatCode;
                throw(checkIVA);
            } else {
                company = checkCompanyVat(vatCode.val());
                if (!company) {                    
                    if (business_name != "" && address != "" && city != "" && province_id != "") {
                        $.ajax({
                            async: false,
                            method: 'POST',
                            url: '/ecommerce/company.php',
                            data: {
                                op_type: 'nuova_company',
                                business_name: business_name,
                                vat: vatCode.val(),
                                address: address,
                                postal_code: '',
                                city: city,
                                province_id: province_id,
                                is_tutor: 0,
                                is_partner: 0,
                                owner_user_id: Q,
                                tutor_id: tutor_company_id,
                                discount: 0,
                                ateco_sector_id: 1,
                                telephone: '',
                                email: P,
                                gmt: 23,
                                contract_id: 4,
                                test_in_the_presence: 'NO',
                                risk_evaluation: 0,
                                iban: '',
                                regional_authorization: '',
                                ateco: ''
                            },
                            success: function(data){
                                if ($.isNumeric(data) && data > 0) {
                                    company = {id: data};
                                } else {
                                    errorTag = vatCode;
                                    throw("Errore nella creazione dell'azienda.");
                                }
                            }
                        });
                    } else {
                        $('.business-data').show();
                        if (business_name == "") errorTag = $('#business_name');
                        else if (address == "") errorTag = $('#address');
                        else if (city == "") errorTag = $('#city');
                        else errorTag = $('#province_id');
                        throw("Inserisci i dati dell'azienda per la fatturazione.");
                    }
                } else if ( company.tutor_id != tutor_company_id ) { //&& (tutor_company_id != 2 || company.ecommerce != "") ) { <-- VERIFICARE PREZZO DI VENDITA DEL TUTOR
                    errorTag = vatCode;
                    throw("Questa p.iva è già presente in Tutor81, registrata da un altro Ente Formativo, per maggiori info scrivi ad assistenza@tutor81.it.");
                } else if (!confirm("I corsi verranno assegnati all'azienda " + company.business_name + '. Confermi?')) {
                    errorTag = vatCode;
                    throw("Non hai confermato l'azienda a cui assegnare i corsi. Correggi la partita IVA se non corrisponde.");
                }
            }
            console.log(N);
            var K = $("input:radio[name=checkout-payments]:checked").val();
            console.log(K);
            if (K == "paypal") {
                
            } else {
                if (K == "bonifico") {
                    $.post("/ecommerce/license.php", {
                        op_type: "new_ecommerce_purchase",
                        tutorid: Q,
                        tutor_company_id: tutor_company_id, 
                        customercompany_id: company.id,
                        user_company_ref: company.owner_user_id,
                        learningproject_id: N,
                        amount: T,
                        costcentre_id: L,
                        price: $(".checkoutPrezzo").val(),//R,
                        total: R,
                        payment: K,
                        email: P,
                        code: host_name
                    }).done(function(U) {
                        console.log(U);
                        alert("Grazie. Abbiamo appena inviato le coordinate per effettuare il bonifico bancario, controlla che l'email non sia stata bloccata dal tuo sistema antivirus o sia nella cartella spam.");
                        $("#checkout").prop("disabled", false);
                        w.modal("hide");
                    })
                } else {
                    alert("Attenzione! Nessun metodo di pagamento riconosciuto selezionare il metodo di pagamento.");
                    $("#checkout").prop("disabled", false);
                    checkoutWizard.formwizard("show", "checkout-third");
                }
            }
        } catch(errorText) {
            alert(errorText);
            checkoutWizard.formwizard("show", "checkout-fourth");
            errorTag.focus();
            $("#checkout").prop("disabled", false);
            return false;
        }
    });
    var c = function(K) {
        $.get(K, function(L) {
            $("#catalogContent").html(L);
            $("[rel=popover]").popover({
                html: true,
                trigger: "hover",
                placement: "top",
                container: ".store-item-info",
                content: function() {
                    return $(this).parent("div").children(".mycontent").html();
                }
            });
        });
    };
    $(".btnCourse").on("click", function() {
        $("#tuttiLink").removeClass("active");
        return false;
    });
    c("/ec-course-list.php?cat_id=5");
    $("a.btnCourse").click(function() {
        $("li.active:not(.cat-tab)").removeClass("active");
        $(this).parent('li').addClass("active");
        c($(this).attr("href"));
    });
    $(".cat-tab").click(function() {
        $("li.active:not(.cat-tab)").removeClass("active");
        $(".all").addClass("active");
    });
    $(".cat-tab > a").on('shown.bs.tab', function(e){
        var target = $(e.target).attr('href');
        c($(target).find('.all > a').attr('href'));
    });
});