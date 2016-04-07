window.addEvent('domready', function() {
	var Slideshow = new Class({
		initialize: function(config) {
			this.images = config.imageSet;
			this.currentImage = config.startImage;
			this.currentIndex = config.startIndex;
			this.nextImage.delay(3000, this);
			
			// Find the image element and copy its attributes
			this.imageEl = document.getElementsByClassName("mod_swg_slideshow")[0];
		},
		
		nextImage: function() {
			// Pick any image other than the current one
			var nextImageIndex;
			do {
				nextImageIndex = Math.floor(Math.random()*this.images.length);
			} while (nextImageIndex == this.currentIndex);
			
			// Create new image
			var newElement = new Element("img", {
				src:this.images[nextImageIndex]
			});
			newElement.className = this.imageEl.className;
			newElement.setStyle("position", "absolute");
			newElement.setStyle("opacity", 0);
			this.imageEl.parentNode.insertBefore(newElement, this.imageEl);
			
			// Construct fade animations. Need to be able to catch events when they complete so the fade shortcut is no good
			var self = this;
			var fadeInNew = new Fx.Tween(newElement, {
				property:	"opacity",
				duration:	"long",
				onComplete:	function() {
					self.imageEl.parentNode.removeChild(self.imageEl);
					newElement.setStyle("position", "static");
					self.imageEl = newElement;
					self.nextImage.delay(3000, self);
				}
			});
			var fadeOutOld = new Fx.Tween(this.imageEl, {
				property:	"opacity",
				duration:	"long"
			});
			fadeInNew.start(1);
			fadeOutOld.start(0);
			
		}
	});
	
	var slideshowInstance = new Slideshow(JSON.decode(mod_swg_slideshow_config));
});