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
            $("#cerca-utenti-modal").modal()
        }
    },
    html: true,
    open: function(h, i) {
        $(".ui-autocomplete").css("z-index", 1000)
    }
});