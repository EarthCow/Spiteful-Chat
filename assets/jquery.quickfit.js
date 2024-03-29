!(function (t) {
  var e, i, s, n;
  (n = "quickfit"),
    (s = {
      min: 8,
      max: 12,
      tolerance: 0.02,
      truncate: !1,
      width: null,
      sampleNumberOfLetters: 10,
      sampleFontSize: 12,
    }),
    (i = (function () {
      var e = null;

      function i(e) {
        (this.options = e),
          (this.item = t('<span id="meassure"></span>')),
          this.item.css({
            position: "absolute",
            left: "-1000px",
            top: "-1000px",
            "font-size": "" + this.options.sampleFontSize + "px",
          }),
          t("body").append(this.item),
          (this.meassures = {});
      }
      return (
        (i.instance = function (t) {
          return e || (e = new i(t)), e;
        }),
        (i.prototype.getMeassure = function (t) {
          var e;
          return (e = this.meassures[t]) || (e = this.setMeassure(t)), e;
        }),
        (i.prototype.setMeassure = function (t) {
          var e, i, s, n, h;
          for (
            i = 0,
              n = "",
              s = " " === t ? "&nbsp;" : t,
              h = this.options.sampleNumberOfLetters - 1;
            0 <= h ? i <= h : i >= h;
            0 <= h ? i++ : i--
          )
            n += s;
          return (
            this.item.html(n),
            (e =
              this.item.width() /
              this.options.sampleNumberOfLetters /
              this.options.sampleFontSize),
            (this.meassures[t] = e),
            e
          );
        }),
        i
      );
    })()),
    (e = (function () {
      function e(e, h) {
        (this.$element = e),
          (this.options = t.extend({}, s, h)),
          (this.$element = t(this.$element)),
          (this._defaults = s),
          (this._name = n),
          (this.quickfitHelper = i.instance(this.options));
      }
      return (
        (e.prototype.fit = function () {
          var t;
          return (
            this.options.width ||
              ((t = this.$element.width()),
              (this.options.width = t - this.options.tolerance * t)),
            (this.text = this.$element.attr("data-quickfit"))
              ? (this.previouslyTruncated = !0)
              : (this.text = this.$element.text()),
            this.calculateFontSize(),
            this.options.truncate && this.truncate(),
            {
              $element: this.$element,
              size: this.fontSize,
            }
          );
        }),
        (e.prototype.calculateFontSize = function () {
          var t, e, i;
          for (i = 0, e = 0; i < this.text.length; ++i)
            (t = this.text.charAt(i)),
              (e += this.quickfitHelper.getMeassure(t));
          return (
            (this.targetFontSize = parseInt(this.options.width / e)),
            (this.fontSize = Math.max(
              this.options.min,
              Math.min(this.options.max, this.targetFontSize),
            ))
          );
        }),
        (e.prototype.truncate = function () {
          var t, e, i, s, n;
          if (this.fontSize > this.targetFontSize) {
            for (
              s = "",
                n = 3 * this.quickfitHelper.getMeassure(".") * this.fontSize,
                t = 0;
              n < this.options.width && t < this.text.length;

            )
              (i = this.text[t++]),
                e && (s += e),
                (n += this.fontSize * this.quickfitHelper.getMeassure(i)),
                (e = i);
            return (
              s.length + 1 === this.text.length
                ? (s = this.text)
                : (s += "..."),
              (this.textWasTruncated = !0),
              this.$element.attr("data-quickfit", this.text).html(s)
            );
          }
          if (this.previouslyTruncated) return this.$element.html(this.text);
        }),
        e
      );
    })()),
    (t.fn.quickfit = function (t) {
      for (
        var i = [],
          s = this.each(function () {
            var s = new e(this, t).fit();
            return i.push(s), s.$element;
          }),
          n = 0;
        n < i.length;
        n++
      ) {
        var h = i[n];
        h.$element.css({
          fontSize: h.size + "px",
        });
      }
      return s;
    });
})(jQuery, window);
