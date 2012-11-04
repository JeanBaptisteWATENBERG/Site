(function(r){Galleria.addTheme({
	name:"twelve",
	author:"Galleria",
	css:"galleria.classic.css",
	defaults:{
		transition:"pulse",
		transitionSpeed:500,
		imageCrop:!0,
		thumbCrop:!0,
		carousel:!1,
		_locale:{
			show_thumbnails:"Show thumbnails",
			hide_thumbnails:"Hide thumbnails",
			play:"Play slideshow",
			pause:"Pause slideshow",
			enter_fullscreen:"Enter fullscreen",
			exit_fullscreen:"Exit fullscreen",
			popout_image:"Popout image",
			showing_image:"Showing image %s of %s"
		},
		_showFullscreen:!0,
		//_showPopout:!0,
		_showProgress:!0,
		_showTooltip:!0
	},
	init:function(b){
		this.addElement("bar","fullscreen","play","popout","thumblink","s1","s2","s3","s4","progress");
		this.append({stage:"progress",container:["bar","tooltip"],bar:["fullscreen","play","popout","thumblink","info","s1","s2","s3","s4"]});
		this.prependChild("info","counter");
		var a=this,
			n=this.$("thumbnails-container"),
			i=this.$("thumblink"),
			f=this.$("fullscreen"),
			j=this.$("play"),
			k=this.$("popout"),
			h=this.$("bar"),
			l=this.$("progress"),
			s=b.transition,
			c=b._locale,
			d=!1,
			m=!1,
			g=!!b.autoplay,o=!1,
			p=function(){
				n.height(a.getStageHeight()).width(a.getStageWidth()).css("top",d?0:a.getStageHeight()+30)
			},
			q=function(){
				d&&o?a.play(2000):(o=g,a.pause());
				Galleria.utils.animate(
					n,
					{top:d?a.getStageHeight()+30:0},
					{easing:"galleria",
						duration:400,
						complete:function(){
							a.defineTooltip("thumblink",d?c.show_thumbnails:c.hide_thumbnails);
							i[d?"removeClass":"addClass"]("open");
							d=!d
						}
					}
				)
			};
			p();
			b._showTooltip&&a.bindTooltip({
				thumblink:c.show_thumbnails,
				fullscreen:c.enter_fullscreen,
				play:c.play,
				popout:c.popout_image,
				caption:function(){
					var e=a.getData(),b="";
					e&&(e.title&&e.title.length&&(b+="<strong>"+e.title+"</strong>"),e.description&&e.description.length&&(b+="<br>"+e.description));
					return b
				},
				counter:function(){
					return c.showing_image.replace(/\%s/,a.getIndex()+1).replace(/\%s/,a.getDataLength())
				}
			
			});
			b.showInfo||this.$("info").hide();
			this.bind("play",function(){g=!0;j.addClass("playing")});
			this.bind("pause",function(){g=!1;j.removeClass("playing");
			l.width(0)});
			b._showProgress&&this.bind("progress",function(a){l.width(a.percent/100*this.getStageWidth())});
			this.bind("loadstart",function(a){a.cached||this.$("loader").show()});
			this.bind("loadfinish",function(){l.width(0);this.$("loader").hide();
			this.refreshTooltip("counter","caption")});
			this.bind("thumbnail",function(b){r(b.thumbTarget).hover(function(){a.setInfo(b.thumbOrder);
			a.setCounter(b.thumbOrder)},function(){a.setInfo();
			a.setCounter()}).click(function(){q()})});
			this.bind("fullscreen_enter",function(){m=!0;a.setOptions("transition",!1);f.addClass("open");
			h.css("bottom",0);
			this.defineTooltip("fullscreen",c.exit_fullscreen);Galleria.TOUCH||this.addIdleState(h,{bottom:-31})});
			this.bind("fullscreen_exit",function(){m=!1;Galleria.utils.clearTimer("bar");a.setOptions("transition",s);
			f.removeClass("open");h.css("bottom",0);
			this.defineTooltip("fullscreen",c.enter_fullscreen);Galleria.TOUCH||this.removeIdleState(h,{bottom:-31})});
			this.bind("rescale",p);
			Galleria.TOUCH||(this.addIdleState(this.get("image-nav-left"),{left:-36}),this.addIdleState(this.get("image-nav-right"),{right:-36}));
			i.click(q);b._showPopout?
			k.click(function(b){a.openLightbox();
			b.preventDefault()}):(k.remove(),b._showFullscreen&&(this.$("s4").remove(),this.$("info").css("right",40),f.css("right",0)));
			j.click(function(){a.defineTooltip("play",g?c.play:c.pause);
			g?a.pause():(d&&i.click(),a.play(2000))});
			b._showFullscreen?f.click(function(){m?a.exitFullscreen():a.enterFullscreen()})
			:(f.remove(),b._show_popout&&(this.$("s4").remove(),this.$("info").css("right",40),k.css("right",0)));
			!b._showFullscreen&&!b._showPopout&&(this.$("s3,s4").remove(),this.$("info").css("right",10));b.autoplay&&this.trigger("play")
	}
})})(jQuery);