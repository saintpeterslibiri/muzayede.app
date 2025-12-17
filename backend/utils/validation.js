// this fubstion control a value empty or not
// null, undefined, emty string, just empty = true

function isEmpty(value) {
    
    //we use  == because null == undefined returns true
    if (value == null) {
        return true;
    }
    
    
    // typeof: returns a values type
    if (typeof value === 'string') {
        // trim(): cut the values emytnes end and begin
        // '  hello  '.trim() = 'hello'
        return value.trim().length === 0;
    }
    
    // otherwise return false
    return false;
}

//   isValidEmail('test@example.com')  // true
//   isValidEmail('invalid-email')      // false

function isValidEmail(email) {
    
    if (isEmpty(email)) {
        return false;
    }
    
    // Email regex pattern
    // ^ : String başlangıcı
    // [^\s@]+ : Boşluk ve @ hariç en az 1 karakter
    // @ : @ sembolü
    // [^\s@]+ : Boşluk ve @ hariç en az 1 karakter (domain)
    // \. : Nokta
    // [^\s@]+ : Boşluk ve @ hariç en az 1 karakter (uzantı)
    // $ : String sonu
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    // test(): Regex'in string ile eşleşip eşleşmediğini kontrol eder
    return emailRegex.test(email);
}


// -------------------------------
// Minimum uzunluk kontrolü
// ------------------------------------------
// String belirli uzunlukta mı kontrol eder

// Kullanım:
//   hasMinLength('test', 3)    // true (4 >= 3)
//   hasMinLength('ab', 3)      // false (2 < 3)

function hasMinLength(value, minLength) {
    if (isEmpty(value)) {
        return false;
    }
    
    return value.trim().length >= minLength;
}



// Maksimum uzunluk kontrolü


function hasMaxLength(value, maxLength) {
    if (isEmpty(value)) {
        return true; // Boş değer max'ı aşmaz
    }
    
    return value.trim().length <= maxLength;
}



function isValidNumber(value) {
    if (isEmpty(value)) {
        return false;
    }
    
    // parseFloat(): String'i ondalıklı sayıya çevirir
    // isNaN(): "Not a Number" mı kontrol eder
    // isFinite(): Sonsuz değil mi kontrol eder
    const num = parseFloat(value);
    return !isNaN(num) && isFinite(num);
}



// Pozitif sayı kontrolü

// Fiyatlar için kullanılacak (negatif fiyat olamaz)

function isPositiveNumber(value) {
    if (!isValidNumber(value)) {
        return false;
    }
    
    return parseFloat(value) > 0;
}



// Tarih kontrolü

// Geçerli tarih formatı mı?

// Kullanım:
//   isValidDate('2025-12-31')           // true
//   isValidDate('2025-12-31T10:30')     // true
//   isValidDate('invalid')              // false

function isValidDate(value) {
    if (isEmpty(value)) {
        return false;
    }
    
    // Date.parse(): Tarihi milisaniyeye çevirir, geçersizse NaN döner
    const timestamp = Date.parse(value);
    return !isNaN(timestamp);
}



// Gelecek tarih kontrolü

// Açık artırma bitiş tarihi geçmişte olamaz

function isFutureDate(value) {
    if (!isValidDate(value)) {
        return false;
    }
    
    const inputDate = new Date(value);
    const now = new Date();
    
    return inputDate > now;
}


// Enum değer kontrolü

// Değer belirlenen seçeneklerden biri mi?
//
// Kullanım:
//   isValidEnum('electronics', ['electronics', 'collectibles', 'other'])  // true
//   isValidEnum('invalid', ['electronics', 'collectibles', 'other'])      // false

function isValidEnum(value, allowedValues) {
    if (isEmpty(value)) {
        return false;
    }
    
    // includes(): Dizi içinde değer var mı kontrol eder
    return allowedValues.includes(value);
}


// -----------------------------------------------------
// Auction (Açık Artırma) doğrulama
// -----------------------------------------------------
// Yeni açık artırma oluştururken tüm alanları kontrol eder
// Hata varsa hata mesajı döner, yoksa null döner

function validateAuction(data) {
    const errors = [];
    
    // Başlık kontrolü
    if (isEmpty(data.title)) {
        errors.push('Başlık gerekli');
    } else if (!hasMinLength(data.title, 3)) {
        errors.push('Başlık en az 3 karakter olmalı');
    } else if (!hasMaxLength(data.title, 200)) {
        errors.push('Başlık en fazla 200 karakter olabilir');
    }
    
    // Açıklama kontrolü (opsiyonel ama varsa min uzunluk)
    if (!isEmpty(data.description) && !hasMinLength(data.description, 10)) {
        errors.push('Açıklama en az 10 karakter olmalı');
    }
    
    // Kategori kontrolü
    const validCategories = ['electronics', 'collectibles', 'fashion', 'home', 'sports', 'other'];
    if (isEmpty(data.category)) {
        errors.push('Kategori gerekli');
    } else if (!isValidEnum(data.category, validCategories)) {
        errors.push('Geçersiz kategori');
    }
    
    // Başlangıç fiyatı kontrolü
    if (isEmpty(data.starting_price)) {
        errors.push('Başlangıç fiyatı gerekli');
    } else if (!isPositiveNumber(data.starting_price)) {
        errors.push('Başlangıç fiyatı pozitif bir sayı olmalı');
    }
    
    // Başlangıç zamanı kontrolü
    if (isEmpty(data.start_time)) {
        errors.push('Başlangıç zamanı gerekli');
    } else if (!isValidDate(data.start_time)) {
        errors.push('Geçersiz başlangıç zamanı');
    }
    
    // Bitiş zamanı kontrolü
    if (isEmpty(data.end_time)) {
        errors.push('Bitiş zamanı gerekli');
    } else if (!isValidDate(data.end_time)) {
        errors.push('Geçersiz bitiş zamanı');
    } else if (!isFutureDate(data.end_time)) {
        errors.push('Bitiş zamanı gelecekte olmalı');
    }
    
    // Başlangıç ve bitiş zamanı karşılaştırması
    if (isValidDate(data.start_time) && isValidDate(data.end_time)) {
        const start = new Date(data.start_time);
        const end = new Date(data.end_time);
        if (end <= start) {
            errors.push('Bitiş zamanı başlangıçtan sonra olmalı');
        }
    }
    
    // Hata varsa döndür, yoksa null
    return errors.length > 0 ? errors : null;
}


// -----------------------------------------------------
// Bid (Teklif) doğrulama
// -----------------------------------------------------

function validateBid(data, currentPrice) {
    const errors = [];
    
    // Teklif miktarı kontrolü
    if (isEmpty(data.amount)) {
        errors.push('Teklif miktarı gerekli');
    } else if (!isPositiveNumber(data.amount)) {
        errors.push('Teklif miktarı pozitif bir sayı olmalı');
    } else if (parseFloat(data.amount) <= parseFloat(currentPrice)) {
        errors.push('Teklif mevcut fiyattan yüksek olmalı');
    }
    
    return errors.length > 0 ? errors : null;
}


// -----------------------------------------------------
// Dışa aktar
// -----------------------------------------------------

module.exports = {
    isEmpty,
    isValidEmail,
    hasMinLength,
    hasMaxLength,
    isValidNumber,
    isPositiveNumber,
    isValidDate,
    isFutureDate,
    isValidEnum,
    validateAuction,
    validateBid
};