//本脚本用来批量修改有道词典的标签
$('#wordlist tr').each(function(index,data){
	var word = $(data).find('div.word').attr('title')
	var phonetic = $(data).find('div.phonetic').attr('title')
	var desc = $(data).find('div.desc').attr('title')
	
	$.ajax({
		url: 'http://dict.youdao.com/wordbook/wordlist?action=modify',
		type: 'POST',
		data:{word:word,tags:"river town",phonetic:phonetic,desc:desc},
		dataType: 'html',
		timeout: 1000,
		error: function(){},
		success: function(result){ }
	});
});