flag = true;
$(".ipt").click(function(){

    if(flag){
        $(this).children('strong').html(' -');
        $(this).next('ul').css('display','block');
        flag = false;
    }else{
        flag = true;
        var self = $(this);
        setTimeout(function(){
            self.children('strong').html(' +');
            self.next('ul').css('display','none');
        },200)
    }


});

$(".ipt").blur(function(){
    flag = true;
    var self = $(this);
    setTimeout(function(){
        self.children('strong').html('+');
        self.next('ul').css('display','none');
    },200)
});
