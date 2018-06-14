function showPages(name) {
    this.name = name;
    this.page = 1;
    this.pageCount = 1;
    this.argName = 'page';
    this.showTimes = 1;
}

showPages.prototype.getPage = function () {
    var args = location.search;
    var reg = new RegExp('[\?&]?' + this.argName + '=([^&]*)[&$]?', 'gi');
    var chk = args.match(reg);
    this.page = RegExp.$1;
}
showPages.prototype.checkPages = function () {
    if (isNaN(parseInt(this.page))) this.page = 1;
    if (isNaN(parseInt(this.pageCount))) this.pageCount = 1;
    if (this.page < 1) this.page = 1;
    if (this.pageCount < 1) this.pageCount = 1;
    if (this.page > this.pageCount) this.page = this.pageCount;
    this.page = parseInt(this.page);
    this.pageCount = parseInt(this.pageCount);
}
showPages.prototype.createHtml = function (mode) {
    var strHtml = '<ul class="am-pagination tpl-pagination">', prevPage = this.page - 1,
        nextPage = this.page + 1;
    if (mode == '' || typeof(mode) == 'undefined') mode = 2;
    switch (mode) {
        case 2:
            if (prevPage < 1) {
                strHtml += '<li class="am-disabled"><a href="javascript:void(null);">&laquo;&laquo;</a></li>';
                strHtml += '<li class="am-disabled"><a href="javascript:void(null);">&laquo;</a></li>';
            } else {
                strHtml += '<li><a href="javascript:' + this.name + '.toPage(1);">&laquo;&laquo;</a></li>';
                strHtml += '<li><a href="javascript:' + this.name + '.toPage(' + prevPage + ');">&laquo;</a></li>';
            }
            if (this.page != 1) strHtml += '<li><a href="javascript:' + this.name + '.toPage(1);">1</a></li>';
            if (this.page >= 5) strHtml += '<li><a href="javascript:void(0);">...</a></li>';
            if (this.pageCount > this.page + 2) {
                var endPage = this.page + 2;
            } else {
                var endPage = this.pageCount;
            }
            for (var i = this.page - 2; i <= endPage; i++) {
                if (i > 0) {
                    if (i == this.page) {
                        strHtml += '<li class="am-active"><a href="javascript:void(null);">' + i + '</a></li>';
                    } else {
                        if (i != 1 && i != this.pageCount) {
                            strHtml += '<li><a href="javascript:' + this.name + '.toPage(' + i + ');">' + i + '</a></li>';
                        }
                    }
                }
            }
            if (this.page + 3 < this.pageCount) strHtml += '<li><a href="javascript:void(0);">...</a></li>';
            if (this.page != this.pageCount) strHtml += '<li><a href="javascript:' + this.name + '.toPage(' + this.pageCount + ');">' + this.pageCount + '</a></li>';
            if (nextPage > this.pageCount) {
                strHtml += '<li class="am-disabled"><a href="javascript:void(null);">&raquo;</a></li>';
                strHtml += '<li class="am-disabled"><a href="javascript:void(null);">&raquo;&raquo;</a></li>';
            } else {
                strHtml += '<li><a href="javascript:' + this.name + '.toPage(' + nextPage + ');">&raquo;</a></li>';
                strHtml += '<li><a href="javascript:' + this.name + '.toPage(' + this.pageCount + ');">&raquo;&raquo;</a></li>';
            }
            strHtml += '</ul>';
            break;
        default:
            strHtml = 'Javascript showPage Error: not find mode ' + mode;
            break;
    }
    return strHtml;
}
showPages.prototype.createUrl = function (page) {
    if (isNaN(parseInt(page))) page = 1;
    if (page < 1) page = 1;
    if (page > this.pageCount) page = this.pageCount;
    var url = location.protocol + '//' + location.host + location.pathname;
    var args = location.search;
    var reg = new RegExp('([\?&]?)' + this.argName + '=[^&]*[&$]?', 'gi');
    args = args.replace(reg, '$1');
    if (args == '' || args == null) {
        args += '?' + this.argName + '=' + page;
    } else if (args.substr(args.length - 1, 1) == '?' || args.substr(args.length - 1, 1) == '&') {
        args += this.argName + '=' + page;
    } else {
        args += '&' + this.argName + '=' + page;
    }
    return url + args;
}
showPages.prototype.toPage = function (page) {
    var turnTo = 1;
    if (typeof(page) == 'object') {
        turnTo = page.options[page.selectedIndex].value;
    } else {
        turnTo = page;
    }
    self.location.href = this.createUrl(turnTo);
}
showPages.prototype.printHtml = function (mode) {
    this.getPage();
    this.checkPages();
    this.showTimes += 1;
    document.write('' + this.createHtml(mode) + '');
}
showPages.prototype.formatInputPage = function (e) {
    var ie = navigator.appName == "Microsoft Internet Explorer" ? true : false;
    if (!ie) var key = e.which; else var key = event.keyCode;
    if (key == 8 || key == 46 || (key >= 48 && key <= 57)) return true;
    return false;
}