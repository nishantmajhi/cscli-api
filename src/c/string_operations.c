/* Unit 6: Arrays
 * Character arrays and strings. Reading/writing strings and using
 * the string library functions: strlen, strcpy, strcat, strcmp. */
#include <stdio.h>
#include <string.h>

int main(void) {
    char s1[50] = "Hello";
    char s2[50] = "World";
    char dest[50];

    printf("s1 = \"%s\", length = %zu\n", s1, strlen(s1));

    strcpy(dest, s1);
    printf("strcpy(dest, s1) -> dest = \"%s\"\n", dest);

    strcat(dest, " ");
    strcat(dest, s2);
    printf("strcat(dest, \" \", s2) -> dest = \"%s\"\n", dest);

    int cmp = strcmp(s1, s2);
    printf("strcmp(s1, s2) = %d\n", cmp);

    return 0;
}
