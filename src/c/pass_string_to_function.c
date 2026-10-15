/* Unit 7: Functions
 * Passing strings to a function - counts vowels in a string. */
#include <stdio.h>

int countVowels(char str[]) {
    int count = 0;
    for (int i = 0; str[i] != '\0'; i++) {
        char c = str[i];
        if (c=='a'||c=='e'||c=='i'||c=='o'||c=='u'||
            c=='A'||c=='E'||c=='I'||c=='O'||c=='U') {
            count++;
        }
    }
    return count;
}

int main(void) {
    char sentence[100] = "Programming in C is fun";
    printf("Vowel count in \"%s\" = %d\n", sentence, countVowels(sentence));
    return 0;
}
